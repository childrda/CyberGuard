<?php

namespace App\Services;

use App\Jobs\SendPhishingSimulationJob;
use App\Models\PhishingCampaign;
use App\Models\PhishingCampaignTarget;
use App\Models\PhishingMessage;
use App\Models\PhishingEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Campaign engine: build targets, create messages, queue sends.
 * Only allows sending to approved domains. Hard block external.
 */
class PhishingCampaignService
{
    public function __construct(
        protected DomainGuardService $domainGuard,
        protected AuditService $audit
    ) {}

    /**
     * Resolve targets for a campaign into a list of recipient emails.
     * Supports user, group, csv target types.
     */
    public function resolveTargets(PhishingCampaign $campaign): array
    {
        $targets = $campaign->targets;
        $emails = [];
        foreach ($targets as $target) {
            match ($target->target_type) {
                'user' => $emails[] = ['email' => $target->target_identifier, 'name' => $target->display_name],
                'group' => $emails = array_merge($emails, $this->resolveGroup($target->target_identifier)),
                'csv' => $emails = array_merge($emails, $this->resolveCsvTarget($target)),
                default => null,
            };
        }
        $seen = [];
        $out = [];
        foreach ($emails as $r) {
            $email = strtolower(is_array($r) ? ($r['email'] ?? '') : $r);
            if ($email && !isset($seen[$email])) {
                $seen[$email] = true;
                $out[] = is_array($r) ? $r : ['email' => $r, 'name' => null];
            }
        }
        return $out;
    }

    private function resolveGroup(string $groupEmail): array
    {
        // Placeholder: later integrate Google Admin SDK / Directory API to resolve group members.
        return [['email' => $groupEmail, 'name' => null]];
    }

    private function resolveCsvTarget(PhishingCampaignTarget $target): array
    {
        $meta = $target->metadata ?? [];
        $list = $meta['emails'] ?? [];
        return array_map(fn ($e) => is_array($e) ? $e : ['email' => $e, 'name' => null], $list);
    }

    /**
     * Validate all recipients are in allowed domains, then create PhishingMessage records and queue jobs.
     */
    public function launchCampaign(PhishingCampaign $campaign): array
    {
        $allowedDomains = $campaign->allowed_domains ?? config('phishing.allowed_target_domains', []);
        if (empty($allowedDomains)) {
            return ['ok' => false, 'error' => 'No allowed domains configured. Set PHISHING_ALLOWED_DOMAINS or campaign allowed_domains.'];
        }

        $recipients = $this->resolveTargets($campaign);
        $rejected = [];
        $accepted = [];
        foreach ($recipients as $r) {
            $email = $r['email'] ?? $r;
            $email = is_string($email) ? $email : ($r['email'] ?? '');
            if (! $this->domainGuard->isAllowed($email, $allowedDomains)) {
                $rejected[] = $email;
                continue;
            }
            $accepted[] = $r;
        }

        if (empty($accepted)) {
            return ['ok' => false, 'error' => 'No recipients passed domain guard.', 'rejected' => $rejected];
        }

        DB::beginTransaction();
        try {
            $campaign->update(['status' => 'sending', 'started_at' => $campaign->started_at ?? now()]);
            $this->audit->log('campaign_launched', $campaign, null, ['recipients_count' => count($accepted), 'rejected_count' => count($rejected)]);

            $campaign->load('attacks');
            $attackIds = $campaign->attacks->where('active', true)->pluck('id')->all();

            foreach ($accepted as $r) {
                $email = is_array($r) ? ($r['email'] ?? '') : $r;
                $name = is_array($r) ? ($r['name'] ?? null) : null;
                $attackId = ! empty($attackIds) ? $attackIds[array_rand($attackIds)] : null;
                $msg = PhishingMessage::create([
                    'campaign_id' => $campaign->id,
                    'attack_id' => $attackId,
                    'recipient_email' => $email,
                    'recipient_name' => $name,
                    'tracking_token' => $this->generateTrackingToken(),
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);
                PhishingEvent::create([
                    'message_id' => $msg->id,
                    'event_type' => 'queued',
                    'occurred_at' => now(),
                ]);
                SendPhishingSimulationJob::dispatch($msg);
            }

            DB::commit();
            return ['ok' => true, 'accepted' => count($accepted), 'rejected' => count($rejected), 'rejected_list' => $rejected];
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateTrackingToken(): string
    {
        return Str::random(64);
    }
}

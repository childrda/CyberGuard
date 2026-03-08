<?php

namespace App\Services;

use App\Jobs\SendPhishingSimulationJob;
use App\Models\PhishingCampaign;
use App\Models\PhishingCampaignTarget;
use App\Models\PhishingMessage;
use App\Models\PhishingEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Campaign engine: build targets, create messages, queue sends.
 * Only allows sending to approved domains. Hard block external.
 * Group targets are resolved via Google Workspace Directory API (one email per person).
 */
class PhishingCampaignService
{
    public function __construct(
        protected DomainGuardService $domainGuard,
        protected AuditService $audit,
        protected GoogleGroupService $googleGroupService
    ) {}

    /**
     * Resolve targets for a campaign into a list of recipient emails.
     * Supports user, group, csv target types.
     */
    public function resolveTargets(PhishingCampaign $campaign): array
    {
        $campaign->load('tenant');
        $targets = $campaign->targets;
        $emails = [];
        foreach ($targets as $target) {
            match ($target->target_type) {
                'user' => $emails[] = ['email' => $target->target_identifier, 'name' => $target->display_name],
                'group' => $emails = array_merge($emails, $this->resolveGroup($campaign, $target->target_identifier)),
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

    /**
     * Resolve a Google Workspace group to individual member emails. Each person in the group
     * gets one entry so the campaign sends one phishing email per person (realistic simulation).
     */
    private function resolveGroup(PhishingCampaign $campaign, string $groupEmail): array
    {
        $tenant = $campaign->tenant;
        if (! $tenant) {
            return [];
        }
        return $this->googleGroupService->listGroupMemberEmails($tenant, $groupEmail);
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

        $useWindow = $campaign->window_start
            && $campaign->window_end
            && $campaign->emails_per_recipient >= 1
            && $campaign->window_end->gte($campaign->window_start);

        DB::beginTransaction();
        try {
            $campaign->update(['status' => 'sending', 'started_at' => $campaign->started_at ?? now()]);
            $totalScheduled = 0;
            if ($useWindow) {
                $totalScheduled = count($accepted) * $campaign->emails_per_recipient;
            }
            $this->audit->log('campaign_launched', $campaign, null, [
                'recipients_count' => count($accepted),
                'rejected_count' => count($rejected),
                'scheduled_over_window' => $useWindow,
                'emails_per_recipient' => $useWindow ? $campaign->emails_per_recipient : 1,
                'total_to_send' => $useWindow ? $totalScheduled : count($accepted),
            ]);

            $campaign->load('attacks');
            $attackIds = $campaign->attacks->where('active', true)->pluck('id')->all();

            if ($useWindow) {
                $windowStart = Carbon::parse($campaign->window_start)->startOfDay();
                $windowEnd = Carbon::parse($campaign->window_end)->endOfDay();
                $tsMin = $windowStart->timestamp;
                $tsMax = max($tsMin, $windowEnd->timestamp);

                foreach ($accepted as $r) {
                    $email = is_array($r) ? ($r['email'] ?? '') : $r;
                    $name = is_array($r) ? ($r['name'] ?? null) : null;
                    for ($i = 0; $i < $campaign->emails_per_recipient; $i++) {
                        $scheduledFor = Carbon::createFromTimestamp(mt_rand($tsMin, $tsMax));
                        $attackId = ! empty($attackIds) ? $attackIds[array_rand($attackIds)] : null;
                        $msg = PhishingMessage::create([
                            'campaign_id' => $campaign->id,
                            'attack_id' => $attackId,
                            'recipient_email' => $email,
                            'recipient_name' => $name,
                            'tracking_token' => $this->generateTrackingToken(),
                            'status' => 'scheduled',
                            'scheduled_for' => $scheduledFor,
                        ]);
                    }
                }
            } else {
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
            }

            DB::commit();
            $totalMessages = $useWindow
                ? count($accepted) * $campaign->emails_per_recipient
                : count($accepted);
            return [
                'ok' => true,
                'accepted' => count($accepted),
                'rejected' => count($rejected),
                'rejected_list' => $rejected,
                'scheduled_over_window' => $useWindow,
                'total_messages' => $totalMessages,
            ];
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

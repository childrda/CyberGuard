<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\PhishingReport;
use App\Models\ReportedMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook for Gmail "Report Phish" add-on. Multi-tenant: resolves tenant by domain,
 * verifies with tenant's webhook secret, stores report with tenant_id and correlation_id.
 */
class ReportWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('phishing.gmail_report_addon_enabled', true)) {
            return response()->json(['error' => 'Add-on disabled'], 503);
        }

        $payload = $request->getContent();
        $data = json_decode($payload, true) ?: [];
        $reporterEmail = $data['reporter_email'] ?? $data['user_email'] ?? null;
        $tenantDomain = $request->header('X-Tenant-Domain') ?? $data['tenant_domain'] ?? null;
        if (! $tenantDomain && $reporterEmail && is_string($reporterEmail) && str_contains($reporterEmail, '@')) {
            $tenantDomain = substr($reporterEmail, strrpos($reporterEmail, '@') + 1);
        }
        $tenant = $tenantDomain ? \App\Models\Tenant::where('domain', $tenantDomain)->orWhere('slug', $tenantDomain)->first() : null;

        if (! $tenant) {
            Log::warning('Report webhook: tenant could not be resolved');
            return response()->json(['error' => 'Unknown tenant'], 422);
        }
        if (empty($tenant->webhook_secret)) {
            Log::warning('Report webhook: tenant has no webhook secret');
            return response()->json(['error' => 'Configuration error'], 500);
        }
        $signature = $request->header('X-Phish-Signature') ?? $request->header('X-Webhook-Signature') ?? '';
        $expected = 'sha256='.hash_hmac('sha256', $payload, $tenant->webhook_secret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('Report webhook: invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        $reporterEmail = $data['reporter_email'] ?? $data['user_email'] ?? null;
        $reportType = $data['report_type'] ?? 'phish';
        $allowedReportTypes = ['phish', 'spam', 'safe'];
        if (! in_array($reportType, $allowedReportTypes, true)) {
            $reportType = 'phish';
        }
        $gmailMessageId = $data['gmail_message_id'] ?? $data['message_id'] ?? null;
        $subject = is_string($data['subject'] ?? null) ? mb_substr($data['subject'], 0, 1000) : null;
        $from = $data['from'] ?? null;
        $fromAddress = is_array($from) ? ($from['email'] ?? $from['address'] ?? null) : (is_string($from) ? $from : null);
        $snippet = is_string($data['snippet'] ?? null) ? mb_substr($data['snippet'], 0, 2000) : null;
        $userActions = $data['user_actions'] ?? [];
        $userActions = is_array($userActions) ? array_slice(array_filter(array_map(function ($a) {
            return is_string($a) ? mb_substr($a, 0, 100) : null;
        }, $userActions)), 0, 20) : [];
        $headers = $data['headers'] ?? [];
        $messageIdHeader = null;
        if (is_array($headers)) {
            foreach (['Message-ID', 'message-id'] as $key) {
                if (! empty($headers[$key]) && is_string($headers[$key])) {
                    $messageIdHeader = trim(mb_substr($headers[$key], 0, 500));
                    break;
                }
            }
        }

        if (! $reporterEmail || ! is_string($reporterEmail)) {
            return response()->json(['error' => 'Missing reporter_email'], 422);
        }
        $reporterEmail = trim(mb_substr($reporterEmail, 0, 255));
        if (! filter_var($reporterEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid reporter_email'], 422);
        }

        $phishingMessage = null;
        $campaignQuery = PhishingMessage::query();
        if ($tenant) {
            $campaignQuery->whereHas('campaign', fn ($q) => $q->where('tenant_id', $tenant->id));
        }
        if ($gmailMessageId) {
            $phishingMessage = (clone $campaignQuery)->where('message_id', $gmailMessageId)->first();
        }
        if (! $phishingMessage && $reporterEmail && $subject) {
            $phishingMessage = (clone $campaignQuery)->where('recipient_email', $reporterEmail)
                ->whereHas('campaign.template', fn ($q) => $q->where('subject', $subject))
                ->latest()
                ->first();
        }

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant?->id,
            'correlation_id' => $correlationId,
            'reporter_email' => $reporterEmail,
            'reporter_name' => $data['reporter_name'] ?? null,
            'gmail_message_id' => $gmailMessageId,
            'gmail_thread_id' => $data['gmail_thread_id'] ?? null,
            'subject' => $subject,
            'from_address' => $fromAddress,
            'from_display' => is_array($from) ? ($from['name'] ?? null) : null,
            'to_addresses' => is_array($data['to'] ?? null) ? implode(', ', $data['to']) : ($data['to_addresses'] ?? null),
            'message_date' => isset($data['date']) ? $data['date'] : null,
            'snippet' => $snippet,
            'report_type' => $reportType,
            'source' => 'addon',
            'message_id_header' => $messageIdHeader,
            'phishing_message_id' => $phishingMessage?->id,
            'analyst_status' => $phishingMessage ? 'analyst_confirmed_simulation' : null,
            'user_actions' => $userActions,
            'headers' => $messageIdHeader ? ['Message-ID' => $messageIdHeader] : null,
        ]);

        PhishingReport::create([
            'reported_message_id' => $reported->id,
            'message_id' => $phishingMessage?->id,
            'event_type' => $phishingMessage ? 'reported' : 'reported',
        ]);

        if ($phishingMessage) {
            PhishingEvent::create([
                'message_id' => $phishingMessage->id,
                'event_type' => 'reported',
                'metadata' => ['reported_message_id' => $reported->id],
                'occurred_at' => now(),
            ]);
        }

        if ($tenant && $phishingMessage) {
            $points = config('phishing.scoring.simulation_reported', 50);
            app(\App\Services\ShieldPointsService::class)->award(
                $tenant->id,
                $reporterEmail,
                'simulation_reported',
                $points,
                'Reported simulation',
                $phishingMessage->campaign_id ?? null,
                $reported->id,
                null
            );
            app(\App\Services\Gamification\BadgeService::class)->evaluateForUser(
                $tenant->id,
                $reporterEmail
            );
        }

        return response()->json([
            'ok' => true,
            'reported_message_id' => $reported->id,
            'correlation_id' => $correlationId,
            'matched_simulation' => (bool) $phishingMessage,
        ]);
    }
}

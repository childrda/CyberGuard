<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncReportedMessageToSlackJob;
use App\Models\PhishingReport;
use App\Models\ReportedMessage;
use App\Services\AuditService;
use App\Services\GmailRemovalService;
use App\Services\SlackReportAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportedMessageController extends Controller
{
    public function __construct(
        protected GmailRemovalService $gmailRemoval,
        protected AuditService $audit
    ) {}

    public function index(Request $request): View
    {
        $allowedPerPage = [10, 20, 40, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = ReportedMessage::with('phishingMessage.campaign');

        if ($request->filled('type')) {
            if ($request->type === 'simulation') {
                $query->whereNotNull('phishing_message_id');
            } elseif ($request->type === 'real') {
                $query->whereNull('phishing_message_id');
            }
        }
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereNull('analyst_status');
            } else {
                $query->where('analyst_status', $request->status);
            }
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to.' 23:59:59');
        }

        $reports = $query->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.reports.index', compact('reports', 'perPage', 'allowedPerPage'));
    }

    public function show(ReportedMessage $reported): View
    {
        $this->authorize('view', $reported);
        $reported->load('phishingMessage.campaign.template', 'analyst', 'tenant');
        $gmailRemovalEnabled = config('phishing.gmail_removal_enabled');
        $credsPath = $reported->tenant
            ? trim((string) ($reported->tenant->google_credentials_path ?? ''))
            : '';
        $canPreviewFullMessage = $reported->gmail_message_id
            && $reported->reporter_email
            && $credsPath !== ''
            && is_file($credsPath)
            && is_readable($credsPath);

        $tenant = $reported->tenant;
        $canPushSlack = $tenant
            && $tenant->slack_alerts_enabled
            && trim((string) $tenant->slack_bot_token) !== '';

        return view('admin.reports.show', compact(
            'reported',
            'gmailRemovalEnabled',
            'canPreviewFullMessage',
            'canPushSlack'
        ));
    }

    public function syncSlackNow(ReportedMessage $reported, SlackReportAlertService $slack): RedirectResponse
    {
        $this->authorize('updateStatus', $reported);
        $reported->load('tenant');
        $tenant = $reported->tenant;
        if (! $tenant || ! $tenant->slack_alerts_enabled || trim((string) $tenant->slack_bot_token) === '') {
            return redirect()->back()->with('error', 'Slack alerts are not enabled or the bot token is missing for this tenant.');
        }

        try {
            $slack->syncReportAlert($reported->fresh(['tenant', 'analyst']));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Slack sync failed: '.$e->getMessage());
        }

        $queue = config('phishing.slack_queue');

        return redirect()->back()->with(
            'success',
            'Slack alert sent. If automatic updates still fail, ensure a queue worker is running and processing the "'.$queue.'" queue (see .env PHISHING_SLACK_QUEUE).'
        );
    }

    public function confirmReal(Request $request, ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('updateStatus', $reported);
        $request->validate(['analyst_notes' => ['nullable', 'string', 'max:2000']]);

        $reported->update([
            'analyst_status' => 'analyst_confirmed_real',
            'analyst_id' => auth()->id(),
            'analyst_reviewed_at' => now(),
            'analyst_notes' => $request->analyst_notes ?? $reported->analyst_notes,
        ]);

        PhishingReport::create([
            'reported_message_id' => $reported->id,
            'message_id' => null,
            'event_type' => 'analyst_confirmed_real',
            'metadata' => ['analyst_id' => auth()->id()],
        ]);

        $tenant = $reported->tenant;
        if ($tenant && $reported->reporter_email && $tenant->gamification_enabled) {
            $points = config('phishing.scoring.reported_phish', 50);
            app(\App\Services\ShieldPointsService::class)->award(
                $tenant->id,
                $reported->reporter_email,
                'reported_phish',
                $points,
                'Reported real phishing (analyst confirmed)',
                null,
                $reported->id,
                null
            );
            app(\App\Services\Gamification\BadgeService::class)->evaluateForUser(
                $tenant->id,
                $reported->reporter_email
            );
        }

        $this->audit->log('report_confirmed_real', $reported);
        SyncReportedMessageToSlackJob::dispatch($reported->id);

        return redirect()->route('admin.reports.show', $reported)
            ->with('success', 'Marked as real phishing. You can now remove it from mailboxes below.');
    }

    public function confirmFalsePositive(Request $request, ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('updateStatus', $reported);
        $request->validate(['analyst_notes' => ['nullable', 'string', 'max:2000']]);

        $reported->update([
            'analyst_status' => 'false_positive',
            'analyst_id' => auth()->id(),
            'analyst_reviewed_at' => now(),
            'analyst_notes' => $request->analyst_notes ?? $reported->analyst_notes,
        ]);

        PhishingReport::create([
            'reported_message_id' => $reported->id,
            'message_id' => $reported->phishing_message_id,
            'event_type' => 'false_positive',
            'metadata' => ['analyst_id' => auth()->id()],
        ]);

        $this->audit->log('report_false_positive', $reported);
        SyncReportedMessageToSlackJob::dispatch($reported->id);

        return redirect()->route('admin.reports.show', $reported)->with('success', 'Marked as false positive.');
    }

    public function removeFromReporterMailbox(ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('removeFromMailbox', $reported);
        $result = $this->gmailRemoval->removeFromUserMailbox($reported);

        if ($result['ok']) {
            $reported->update(['reporter_mailbox_cleared_at' => now()]);
            $this->audit->log('report_removed_reporter_mailbox', $reported);
            SyncReportedMessageToSlackJob::dispatch($reported->id);

            return redirect()->route('admin.reports.show', $reported)->with('success', 'Message trashed in reporter\'s mailbox.');
        }

        return redirect()->route('admin.reports.show', $reported)->with('error', $result['error'] ?? 'Removal failed.');
    }

    public function removeFromAllMailboxes(ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('removeFromMailbox', $reported);
        $reported->update(['remediation_via_google_admin' => true]);
        $this->audit->log('report_domain_remediation_google_admin', $reported, null, [
            'note' => 'Domain-wide removal marked for Google Admin investigation tool; CyberGuard did not trash mailboxes.',
        ]);
        SyncReportedMessageToSlackJob::dispatch($reported->id);

        return redirect()->route('admin.reports.show', $reported)
            ->with('success', 'Recorded: domain-wide remediation will be completed in Google Admin (investigation tool). No messages were removed by CyberGuard.');
    }

    /**
     * Raw HTML/plain body as fetched from the reporter's Gmail (for iframe preview).
     */
    public function messageBody(ReportedMessage $reported): Response
    {
        $this->authorize('view', $reported);
        $reported->load('tenant');
        if (! $reported->gmail_message_id || ! $reported->reporter_email) {
            abort(404, 'No Gmail message reference for this report.');
        }
        $tenant = $reported->tenant;
        if (! $tenant) {
            abort(404, 'Report has no tenant.');
        }
        $path = trim((string) ($tenant->google_credentials_path ?? ''));
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            abort(503, 'Tenant Google credentials are not configured or not readable.');
        }

        config([
            'phishing.google_credentials_path' => $path,
            'phishing.google_admin_user' => $tenant->google_admin_user,
            'phishing.google_domain' => $tenant->domain,
            'phishing.gmail_removal_enabled' => true,
        ]);

        $removal = new GmailRemovalService;
        $result = $removal->fetchMessageBodyForPreview($reported);
        if (! $result['ok']) {
            abort(502, $result['error'] ?? 'Could not load message from Gmail.');
        }

        $html = $result['html'] ?? '';
        $plain = $result['plain'] ?? '';
        $body = $html !== ''
            ? $html
            : '<pre style="white-space:pre-wrap;font-family:system-ui,sans-serif;padding:1rem">'.e($plain ?: '(empty body)').'</pre>';

        $csp = "default-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'; "
            ."img-src https: http: data: cid: blob:; style-src 'unsafe-inline'; font-src data: https:";

        return response($body, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', $csp)
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }
}

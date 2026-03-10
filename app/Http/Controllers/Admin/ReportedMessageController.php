<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingReport;
use App\Models\ReportedMessage;
use App\Services\AuditService;
use App\Services\GmailRemovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportedMessageController extends Controller
{
    public function __construct(
        protected GmailRemovalService $gmailRemoval,
        protected AuditService $audit
    ) {}

    public function index(Request $request): View
    {
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

        $reports = $query->latest()->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }

    public function show(ReportedMessage $reported): View
    {
        $this->authorize('view', $reported);
        $reported->load('phishingMessage.campaign.template', 'analyst');
        $gmailRemovalEnabled = config('phishing.gmail_removal_enabled');
        return view('admin.reports.show', compact('reported', 'gmailRemovalEnabled'));
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
        if ($tenant && $reported->reporter_email) {
            app(\App\Services\ShieldPointsService::class)->award(
                $tenant->id,
                $reported->reporter_email,
                'reported_phish',
                10,
                'Reported real phishing (analyst confirmed)',
                null,
                $reported->id,
                null
            );
        }

        $this->audit->log('report_confirmed_real', $reported);

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

        return redirect()->route('admin.reports.show', $reported)->with('success', 'Marked as false positive.');
    }

    public function removeFromReporterMailbox(ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('removeFromMailbox', $reported);
        $result = $this->gmailRemoval->removeFromUserMailbox($reported);

        if ($result['ok']) {
            $this->audit->log('report_removed_reporter_mailbox', $reported);
            return redirect()->route('admin.reports.show', $reported)->with('success', 'Message trashed in reporter\'s mailbox.');
        }

        return redirect()->route('admin.reports.show', $reported)->with('error', $result['error'] ?? 'Removal failed.');
    }

    public function removeFromAllMailboxes(ReportedMessage $reported): RedirectResponse
    {
        $this->authorize('removeFromMailbox', $reported);
        $result = $this->gmailRemoval->removeFromAllMailboxes($reported);

        if ($result['ok']) {
            $this->audit->log('report_removed_all_mailboxes', $reported, null, $result);
            $msg = "Trashed in {$result['trashed_count']} mailbox(es).";
            if (! empty($result['errors'])) {
                $msg .= ' Some errors: '.implode('; ', array_slice($result['errors'], 0, 2));
            }
            return redirect()->route('admin.reports.show', $reported)->with('success', $msg);
        }

        return redirect()->route('admin.reports.show', $reported)->with('error', $result['error'] ?? 'Removal failed.');
    }
}

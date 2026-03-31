<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportedMessage;
use App\Models\RemediationJob;
use App\Services\RemediationPreflightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RemediationController extends Controller
{
    public function __construct(
        protected RemediationPreflightService $preflight
    ) {}

    public function index(Request $request): View
    {
        $allowedPerPage = [10, 20, 40, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $jobs = RemediationJob::with('reportedMessage', 'approver')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.remediation.index', compact('jobs', 'perPage', 'allowedPerPage'));
    }

    public function show(RemediationJob $job): View
    {
        $job->load('reportedMessage', 'approver', 'items', 'mailboxActionLogs');
        return view('admin.remediation.show', compact('job'));
    }

    public function approve(Request $request, ReportedMessage $reported)
    {
        $this->authorize('updateStatus', $reported);
        $reported->load('tenant');
        if (! $reported->tenant_id) {
            return redirect()->back()->with('error', 'Report has no tenant; remediation requires a tenant.');
        }
        if ($reported->tenant->isReportOnly()) {
            return redirect()->back()->with('error', 'Tenant is in report_only mode.');
        }
        $pre = $this->preflight->checkTenant($reported->tenant);
        if (! $pre['ok']) {
            return redirect()->back()->with('error', $pre['error'] ?? 'Remediation preflight failed.');
        }
        $job = RemediationJob::create([
            'tenant_id' => $reported->tenant_id,
            'reported_message_id' => $reported->id,
            'correlation_id' => $reported->correlation_id ?? \Illuminate\Support\Str::uuid()->toString(),
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
            'dry_run' => (bool) $request->input('dry_run', false),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);
        return redirect()->route('admin.remediation.show', $job)->with('success', 'Remediation job created. Run it to execute removal.');
    }

    public function run(ReportedMessage $reported)
    {
        $this->authorize('updateStatus', $reported);
        $reported->load('tenant');
        if (! $reported->tenant) {
            return redirect()->back()->with('error', 'Report has no tenant; remediation requires a tenant.');
        }
        $pre = $this->preflight->checkTenant($reported->tenant);
        if (! $pre['ok']) {
            return redirect()->back()->with('error', $pre['error'] ?? 'Remediation preflight failed.');
        }
        $job = RemediationJob::where('reported_message_id', $reported->id)->latest()->first();
        if (! $job || $job->status !== RemediationJob::STATUS_APPROVED_FOR_REMOVAL) {
            return redirect()->back()->with('error', 'No approved job found.');
        }
        app(\App\Jobs\ProcessRemediationJob::class)->dispatch($job);
        $job->update(['status' => RemediationJob::STATUS_REMOVAL_IN_PROGRESS, 'started_at' => now()]);
        return redirect()->route('admin.remediation.show', $job)->with('success', 'Remediation job queued.');
    }
}

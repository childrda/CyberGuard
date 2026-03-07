@extends('layouts.app')

@section('title', 'Remediation')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Remediation</h1>
    <p class="text-slate-400">Phishing removal jobs and status</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-400">Status</label>
        <select name="status" class="mt-1 rounded border-slate-600 bg-slate-800 text-slate-200 text-sm">
            <option value="">All</option>
            <option value="pending_review" @selected(request('status') === 'pending_review')>Pending review</option>
            <option value="approved_for_removal" @selected(request('status') === 'approved_for_removal')>Approved</option>
            <option value="removal_in_progress" @selected(request('status') === 'removal_in_progress')>In progress</option>
            <option value="removed" @selected(request('status') === 'removed')>Removed</option>
            <option value="dry_run_completed" @selected(request('status') === 'dry_run_completed')>Dry run (simulated)</option>
            <option value="partially_failed" @selected(request('status') === 'partially_failed')>Partially failed</option>
            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
        </select>
    </div>
    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white text-sm hover:bg-blue-700">Filter</button>
</form>

<div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-700">
        <thead class="bg-slate-800/50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-400">Job</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-400">Report</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-400">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-400">Dry run</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-400">Created</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-400">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
            @foreach($jobs as $job)
                <tr class="hover:bg-slate-800/50">
                    <td class="px-4 py-3 text-slate-200">#{{ $job->id }}</td>
                    <td class="px-4 py-3"><a href="{{ route('admin.reports.show', $job->reportedMessage) }}" class="text-blue-400 hover:underline">#{{ $job->reported_message_id }}</a></td>
                    <td class="px-4 py-3">@include('admin.remediation.partials.status-badge', ['job' => $job])</td>
                    <td class="px-4 py-3 text-slate-400">{{ $job->dry_run ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $job->created_at->toDateTimeString() }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.remediation.show', $job) }}" class="text-blue-400 hover:underline">View</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4 text-slate-400">{{ $jobs->links() }}</div>
@endsection

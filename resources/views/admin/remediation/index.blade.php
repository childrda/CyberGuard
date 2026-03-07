@extends('layouts.app')

@section('title', 'Remediation')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Remediation</h1>
    <p class="text-slate-600">Phishing removal jobs and status</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" class="mt-1 rounded border-slate-300 text-sm">
            <option value="">All</option>
            <option value="pending_review" @selected(request('status') === 'pending_review')>Pending review</option>
            <option value="approved_for_removal" @selected(request('status') === 'approved_for_removal')>Approved</option>
            <option value="removal_in_progress" @selected(request('status') === 'removal_in_progress')>In progress</option>
            <option value="removed" @selected(request('status') === 'removed')>Removed</option>
            <option value="partially_failed" @selected(request('status') === 'partially_failed')>Partially failed</option>
            <option value="failed" @selected(request('status') === 'failed')>Failed</option>
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white text-sm">Filter</button>
</form>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Job</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Report</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Dry run</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Created</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($jobs as $job)
                <tr>
                    <td class="px-4 py-3">#{{ $job->id }}</td>
                    <td class="px-4 py-3"><a href="{{ route('admin.reports.show', $job->reportedMessage) }}" class="text-blue-600 hover:underline">#{{ $job->reported_message_id }}</a></td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-medium">{{ $job->status }}</span></td>
                    <td class="px-4 py-3">{{ $job->dry_run ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $job->created_at->toDateTimeString() }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.remediation.show', $job) }}" class="text-slate-600 hover:underline">View</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $jobs->links() }}</div>
@endsection

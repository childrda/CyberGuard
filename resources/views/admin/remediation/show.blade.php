@extends('layouts.app')

@section('title', 'Remediation #'.$job->id)

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Remediation job #{{ $job->id }}</h1>
    <p class="text-slate-600">Report: <a href="{{ route('admin.reports.show', $job->reportedMessage) }}" class="text-blue-600 hover:underline">#{{ $job->reported_message_id }}</a></p>
    <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium mt-2">{{ $job->status }}</span>
</div>

<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Details</h2>
    <dl class="grid gap-2 text-sm">
        <div><dt class="text-slate-500">Dry run</dt><dd>{{ $job->dry_run ? 'Yes' : 'No' }}</dd></div>
        <div><dt class="text-slate-500">Approved by</dt><dd>{{ $job->approver?->name ?? '-' }}</dd></div>
        <div><dt class="text-slate-500">Approved at</dt><dd>{{ $job->approved_at?->toDateTimeString() ?? '-' }}</dd></div>
    </dl>
</div>

<div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
    <h2 class="px-4 py-3 text-sm font-semibold text-slate-700 bg-slate-50">Job items</h2>
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Mailbox</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($job->items as $item)
                <tr>
                    <td class="px-4 py-2 text-sm">{{ $item->mailbox_email }}</td>
                    <td class="px-4 py-2"><span class="rounded px-2 py-0.5 text-xs">{{ $item->status }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<a href="{{ route('admin.remediation.index') }}" class="mt-4 inline-block text-slate-600 hover:underline text-sm">Back to remediation</a>
@endsection

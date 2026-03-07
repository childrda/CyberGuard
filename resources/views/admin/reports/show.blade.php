@extends('layouts.app')

@section('title', 'Report #'.$reported->id)

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold">Report #{{ $reported->id }}</h1>
        <p class="text-slate-600 mt-0.5">{{ $reported->reporter_email }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($reported->phishing_message_id)
            <span class="inline-flex items-center rounded-md bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800">Simulation</span>
        @else
            <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-sm font-medium text-amber-800">Real report</span>
        @endif
        @if($reported->analyst_status)
            @if($reported->analyst_status === 'analyst_confirmed_real')
                <span class="inline-flex items-center rounded-md bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800">Confirmed phishing</span>
            @elseif($reported->analyst_status === 'false_positive')
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-0.5 text-sm font-medium text-slate-700">False positive</span>
            @else
                <span class="inline-flex items-center rounded-md bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800">{{ $reported->analyst_status }}</span>
            @endif
        @else
            <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-0.5 text-sm font-medium text-slate-600">Pending review</span>
        @endif
    </div>
</div>

{{-- Message details --}}
<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Message</h2>
    <dl class="grid gap-3 sm:grid-cols-1">
        <div><dt class="text-sm text-slate-500">Subject</dt><dd class="font-medium">{{ $reported->subject ?? '—' }}</dd></div>
        <div><dt class="text-sm text-slate-500">From</dt><dd>{{ $reported->from_address ?? '—' }}</dd></div>
        <div><dt class="text-sm text-slate-500">To</dt><dd class="text-slate-700">{{ $reported->to_addresses ?? '—' }}</dd></div>
        <div><dt class="text-sm text-slate-500">Reported</dt><dd>{{ $reported->created_at->toDateTimeString() }}</dd></div>
        @if($reported->user_actions && count($reported->user_actions) > 0)
            <div><dt class="text-sm text-slate-500">User said</dt><dd>{{ implode(', ', $reported->user_actions) }}</dd></div>
        @endif
    </dl>
    @if($reported->snippet)
        <div class="mt-4 pt-4 border-t border-slate-100">
            <dt class="text-sm text-slate-500">Snippet</dt>
            <p class="mt-1 text-slate-700 text-sm">{{ Str::limit($reported->snippet, 300) }}</p>
        </div>
    @endif
    @if($reported->phishingMessage)
        <p class="mt-4 pt-4 border-t border-slate-100 text-sm">Campaign: <a href="{{ route('admin.campaigns.show', $reported->phishingMessage->campaign) }}" class="text-blue-600 hover:underline">{{ $reported->phishingMessage->campaign->name }}</a></p>
    @endif
</div>

{{-- Analyst actions --}}
<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Review</h2>

    @if($reported->analyst_reviewed_at)
        <p class="text-sm text-slate-600 mb-4">Reviewed by {{ $reported->analyst?->name ?? 'Analyst' }} on {{ $reported->analyst_reviewed_at->toDateTimeString() }}</p>
        @if($reported->analyst_notes)
            <p class="text-sm text-slate-700 mb-4">{{ $reported->analyst_notes }}</p>
        @endif
    @endif

    @if(! $reported->analyst_status || $reported->analyst_status === 'pending')
        <div class="space-y-4">
            <label class="block text-sm font-medium text-slate-700">Notes (optional)</label>
            <textarea id="analyst_notes" rows="2" class="w-full rounded border-slate-300 text-sm" placeholder="Internal notes...">{{ old('analyst_notes', $reported->analyst_notes) }}</textarea>
            <div class="flex flex-wrap gap-3">
                <form method="post" action="{{ route('admin.reports.confirm-real', $reported) }}" class="inline" onsubmit="document.getElementById('notes_real').value=document.getElementById('analyst_notes').value;">
                    @csrf
                    <input type="hidden" name="analyst_notes" id="notes_real" value="">
                    <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Confirm real phishing</button>
                </form>
                <form method="post" action="{{ route('admin.reports.confirm-false-positive', $reported) }}" class="inline" onsubmit="document.getElementById('notes_fp').value=document.getElementById('analyst_notes').value;">
                    @csrf
                    <input type="hidden" name="analyst_notes" id="notes_fp" value="">
                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Mark false positive</button>
                </form>
            </div>
        </div>
    @endif

    @if($reported->analyst_status === 'analyst_confirmed_real' && $gmailRemovalEnabled)
        <div class="mt-6 pt-6 border-t border-slate-200">
            <h3 class="text-sm font-medium text-slate-700 mb-2">Remove from mailboxes</h3>
            <p class="text-sm text-slate-600 mb-3">Trash this message in Gmail so users no longer see it.</p>
            <div class="flex flex-wrap gap-3">
                <form method="post" action="{{ route('admin.reports.remove-reporter', $reported) }}" class="inline" onsubmit="return confirm('Trash this message in the reporter\'s mailbox?');">
                    @csrf
                    <button type="submit" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Remove from reporter</button>
                </form>
                <form method="post" action="{{ route('admin.reports.remove-all', $reported) }}" class="inline" onsubmit="return confirm('Search all domain mailboxes and trash this message everywhere? This may take a moment.');">
                    @csrf
                    <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Remove from all mailboxes</button>
                </form>
            </div>
        </div>
    @elseif($reported->analyst_status === 'analyst_confirmed_real' && ! $gmailRemovalEnabled)
        <p class="mt-4 text-sm text-slate-500">Enable Gmail removal in config to trash this message from mailboxes.</p>
    @endif

    @if($reported->analyst_status === 'analyst_confirmed_real')
        <div class="mt-6 pt-6 border-t border-slate-200">
            <h3 class="text-sm font-medium text-slate-700 mb-2">Remediation job</h3>
            @php $approvedJob = $reported->remediationJobs()->where('status', \App\Models\RemediationJob::STATUS_APPROVED_FOR_REMOVAL)->latest()->first(); @endphp
            @if(! $approvedJob)
                <form method="post" action="{{ route('admin.remediation.approve', $reported) }}" class="space-y-2">
                    @csrf
                    <label class="inline-flex items-center"><input type="checkbox" name="dry_run" value="1" class="rounded border-slate-300"> Dry run (log only, do not trash)</label>
                    <div><textarea name="approval_notes" rows="2" class="w-full rounded border-slate-300 text-sm" placeholder="Approval notes (optional)"></textarea></div>
                    <button type="submit" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white">Approve for removal</button>
                </form>
            @else
                <p class="text-sm text-slate-600 mb-2">Job approved. <a href="{{ route('admin.remediation.show', $approvedJob) }}" class="text-blue-600 hover:underline">View job #{{ $approvedJob->id }}</a></p>
                <form method="post" action="{{ route('admin.remediation.run', $reported) }}" onsubmit="return confirm('Queue remediation to remove this message from all mailboxes?');">
                    @csrf
                    <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Run remediation</button>
                </form>
            @endif
        </div>
    @endif
</div>

<a href="{{ route('admin.reports.index') }}" class="text-slate-600 hover:text-slate-900 text-sm">← Back to reports</a>
@endsection

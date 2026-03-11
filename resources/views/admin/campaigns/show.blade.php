@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
<div class="mb-8 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ $campaign->name }}</h1>
        <p class="text-slate-400">Template: {{ $campaign->template->name }}</p>
        <span class="mt-2 inline-block rounded px-2 py-0.5 text-xs font-medium
            @if($campaign->status === 'completed') bg-green-600/30 text-green-300
            @elseif($campaign->status === 'sending') bg-blue-600/30 text-blue-300
            @elseif($campaign->status === 'draft') bg-slate-600/50 text-slate-200
            @else bg-amber-600/30 text-amber-300
            @endif">{{ $campaign->status }}</span>
    </div>
    <div class="flex gap-2">
        @can('update', $campaign)
            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-200 hover:bg-slate-700">Edit</a>
        @endcan
        @can('approve', $campaign)
            <form method="post" action="{{ route('admin.campaigns.approve', $campaign) }}" class="inline">
                @csrf
                <button type="submit" class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-500">Approve</button>
            </form>
        @endcan
        @can('launch', $campaign)
            <form method="post" action="{{ route('admin.campaigns.launch', $campaign) }}" class="inline" onsubmit="return confirm('Launch this campaign? Emails will be sent to targets.');">
                @csrf
                <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-500">Launch</button>
            </form>
        @endcan
        @can('cancel', $campaign)
            <form method="post" action="{{ route('admin.campaigns.cancel', $campaign) }}" class="inline" onsubmit="return confirm('Cancel this campaign? All messages will be removed. You can then edit targets, re-approve, and launch again.');">
                @csrf
                <button type="submit" class="rounded border border-red-600 bg-red-900/40 px-4 py-2 text-red-300 hover:bg-red-900/60">Cancel campaign</button>
            </form>
        @endcan
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2 mb-8">
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
        <h2 class="font-semibold mb-2 text-slate-200">Targets</h2>
        <ul class="text-slate-300 space-y-1">
            @foreach($campaign->targets as $t)
                <li>{{ $t->target_type }}: {{ $t->target_identifier }} @if($t->display_name)({{ $t->display_name }})@endif</li>
            @endforeach
        </ul>
        @if($campaign->window_start && $campaign->window_end)
            <p class="mt-3 text-sm text-slate-400"><strong class="text-slate-300">Send window:</strong> {{ $campaign->window_start->format('M j, Y') }} – {{ $campaign->window_end->format('M j, Y') }}</p>
            <p class="text-sm text-slate-400"><strong class="text-slate-300">Emails per recipient:</strong> {{ $campaign->emails_per_recipient ?? 1 }}</p>
        @endif
    </div>
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
        <h2 class="font-semibold mb-2 text-slate-200">Stats</h2>
        @php
            $total = $campaign->messages->count();
            $sent = $campaign->messages->where('status', 'sent')->count();
            $scheduled = $campaign->messages->where('status', 'scheduled')->count();
            $queued = $campaign->messages->where('status', 'queued')->count();
            $failed = $campaign->messages->where('status', 'failed')->count();
        @endphp
        <p class="text-slate-300">Total messages: {{ $total }}</p>
        <p class="text-slate-300">Sent: {{ $sent }}</p>
        @if($scheduled > 0)
            <p class="text-slate-300">Scheduled (to send in window): {{ $scheduled }}</p>
        @endif
        @if($queued > 0)
            <p class="text-slate-300">Queued: {{ $queued }}</p>
        @endif
        @if($failed > 0)
            <p class="text-red-400">Failed: {{ $failed }}</p>
            @can('update', $campaign)
                <form method="post" action="{{ route('admin.campaigns.retry-failed', $campaign) }}" class="inline mt-2">
                    @csrf
                    <button type="submit" class="rounded border border-amber-600 bg-amber-900/40 px-3 py-1.5 text-sm text-amber-300 hover:bg-amber-900/60">Retry failed</button>
                </form>
            @endcan
        @endif
        <p class="mt-2 text-slate-300">Clicks: {{ $campaign->messages->flatMap->events->where('event_type', 'clicked')->count() }}</p>
        <p class="text-slate-300">Reports: {{ $campaign->messages->flatMap->events->where('event_type', 'reported')->count() }}</p>
    </div>
</div>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <h2 class="px-4 py-3 font-semibold text-slate-200 bg-slate-800">Messages</h2>
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Recipient</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Status</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Scheduled for</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Sent at</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Events</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @foreach($campaign->messages as $m)
                <tr>
                    <td class="px-4 py-2 text-slate-200">{{ $m->recipient_email }}</td>
                    <td class="px-4 py-2 text-slate-200">{{ $m->status }}</td>
                    <td class="px-4 py-2 text-slate-300">{{ $m->scheduled_for ? $m->scheduled_for->toDateTimeString() : '—' }}</td>
                    <td class="px-4 py-2 text-slate-300">{{ $m->sent_at ? $m->sent_at->toDateTimeString() : '—' }}</td>
                    <td class="px-4 py-2 text-slate-300">{{ $m->events->pluck('event_type')->unique()->join(', ') ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

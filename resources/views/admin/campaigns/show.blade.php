@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
<div class="mb-8 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold">{{ $campaign->name }}</h1>
        <p class="text-slate-600">Template: {{ $campaign->template->name }}</p>
        <span class="mt-2 inline-block rounded px-2 py-0.5 text-xs font-medium
            @if($campaign->status === 'completed') bg-green-100 text-green-800
            @elseif($campaign->status === 'sending') bg-blue-100 text-blue-800
            @elseif($campaign->status === 'draft') bg-slate-100 text-slate-800
            @else bg-amber-100 text-amber-800
            @endif">{{ $campaign->status }}</span>
    </div>
    <div class="flex gap-2">
        @can('update', $campaign)
            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="rounded border border-slate-300 px-4 py-2 text-slate-700">Edit</a>
        @endcan
        @can('approve', $campaign)
            <form method="post" action="{{ route('admin.campaigns.approve', $campaign) }}" class="inline">
                @csrf
                <button type="submit" class="rounded bg-amber-600 px-4 py-2 text-white">Approve</button>
            </form>
        @endcan
        @can('launch', $campaign)
            <form method="post" action="{{ route('admin.campaigns.launch', $campaign) }}" class="inline" onsubmit="return confirm('Launch this campaign? Emails will be sent to targets.');">
                @csrf
                <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white">Launch</button>
            </form>
        @endcan
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2 mb-8">
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-semibold mb-2">Targets</h2>
        <ul class="text-slate-600">
            @foreach($campaign->targets as $t)
                <li>{{ $t->target_type }}: {{ $t->target_identifier }} @if($t->display_name)({{ $t->display_name }})@endif</li>
            @endforeach
        </ul>
        @if($campaign->window_start && $campaign->window_end)
            <p class="mt-3 text-sm text-slate-600"><strong>Send window:</strong> {{ $campaign->window_start->format('M j, Y') }} – {{ $campaign->window_end->format('M j, Y') }}</p>
            <p class="text-sm text-slate-600"><strong>Emails per recipient:</strong> {{ $campaign->emails_per_recipient ?? 1 }}</p>
        @endif
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-semibold mb-2">Stats</h2>
        @php
            $total = $campaign->messages->count();
            $sent = $campaign->messages->where('status', 'sent')->count();
            $scheduled = $campaign->messages->where('status', 'scheduled')->count();
            $queued = $campaign->messages->where('status', 'queued')->count();
            $failed = $campaign->messages->where('status', 'failed')->count();
        @endphp
        <p>Total messages: {{ $total }}</p>
        <p>Sent: {{ $sent }}</p>
        @if($scheduled > 0)
            <p>Scheduled (to send in window): {{ $scheduled }}</p>
        @endif
        @if($queued > 0)
            <p>Queued: {{ $queued }}</p>
        @endif
        @if($failed > 0)
            <p class="text-red-600">Failed: {{ $failed }}</p>
        @endif
        <p class="mt-2">Clicks: {{ $campaign->messages->flatMap->events->where('event_type', 'clicked')->count() }}</p>
        <p>Reports: {{ $campaign->messages->flatMap->events->where('event_type', 'reported')->count() }}</p>
    </div>
</div>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <h2 class="px-4 py-3 font-semibold bg-slate-50">Messages</h2>
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-700">Recipient</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-700">Status</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-700">Scheduled for</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-700">Sent at</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-slate-700">Events</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($campaign->messages as $m)
                <tr>
                    <td class="px-4 py-2">{{ $m->recipient_email }}</td>
                    <td class="px-4 py-2">{{ $m->status }}</td>
                    <td class="px-4 py-2">{{ $m->scheduled_for ? $m->scheduled_for->toDateTimeString() : '—' }}</td>
                    <td class="px-4 py-2">{{ $m->sent_at ? $m->sent_at->toDateTimeString() : '—' }}</td>
                    <td class="px-4 py-2">{{ $m->events->pluck('event_type')->unique()->join(', ') ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

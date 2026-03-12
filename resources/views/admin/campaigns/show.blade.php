@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
@php
    $tab = trim(explode('?', request('tab', 'overview'))[0]) ?: 'overview';
    if (! in_array($tab, ['overview', 'activity', 'users'], true)) {
        $tab = 'overview';
    }
    $messages = $campaign->messages;
    $sentMessages = $messages->where('status', 'sent');
    $uniqueUsers = $sentMessages->pluck('recipient_email')->unique()->filter()->count();
    $emailsSent = $sentMessages->count();
    $clickEvents = $messages->flatMap->events->where('event_type', 'clicked');
    $usersClicked = $clickEvents->map(fn ($e) => $e->message->recipient_email ?? null)->unique()->filter()->count();
    $emailsClicked = $clickEvents->pluck('message_id')->unique()->count();
    $totalClicks = $clickEvents->count();
    $reportedCount = $messages->flatMap->events->where('event_type', 'reported')->count();
    $scheduled = $messages->where('status', 'scheduled')->count();
    $queued = $messages->where('status', 'queued')->count();
    $failed = $messages->where('status', 'failed')->count();

    $topAttacksClicked = $clickEvents->groupBy(fn ($e) => $e->message->attack_id ?? 0)->map->count()->sortDesc()->take(10);
    $attackNames = \App\Models\PhishingAttack::whereIn('id', $topAttacksClicked->keys()->filter())->pluck('name', 'id');

    $usersWithMostClicks = $clickEvents->groupBy(fn ($e) => $e->message->recipient_email ?? '')->map->count()->sortDesc()->take(10);
    $userNames = $sentMessages->keyBy('recipient_email')->map->recipient_name;
@endphp

{{-- Header --}}
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ $campaign->name }}</h1>
        <p class="mt-1 text-slate-400">Template: {{ $campaign->template->name }}</p>
        <span class="mt-2 inline-block rounded px-2 py-0.5 text-xs font-medium
            @if($campaign->status === 'completed') bg-green-600/30 text-green-300
            @elseif($campaign->status === 'sending') bg-blue-600/30 text-blue-300
            @elseif($campaign->status === 'draft') bg-slate-600/50 text-slate-200
            @else bg-amber-600/30 text-amber-300
            @endif">Type: {{ ucfirst($campaign->status) }}</span>
        <p class="mt-2 text-sm text-slate-500">
            Campaign length: @if($campaign->window_start && $campaign->window_end){{ $campaign->window_start->format('Y-m-d') }} – {{ $campaign->window_end->format('Y-m-d') }}@else — @endif
            @if($campaign->emails_per_recipient > 1) · {{ $campaign->emails_per_recipient }} emails per recipient @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('update', $campaign)
            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-200 hover:bg-slate-700">Edit</a>
        @endcan
        @can('approve', $campaign)
            <form method="post" action="{{ route('admin.campaigns.approve', $campaign) }}" class="inline">@csrf<button type="submit" class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-500">Approve</button></form>
        @endcan
        @can('launch', $campaign)
            <form method="post" action="{{ route('admin.campaigns.launch', $campaign) }}" class="inline" onsubmit="return confirm('Launch this campaign? Emails will be sent to targets.');">@csrf<button type="submit" class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-500">Launch</button></form>
        @endcan
        @can('cancel', $campaign)
            <form method="post" action="{{ route('admin.campaigns.cancel', $campaign) }}" class="inline" onsubmit="return confirm('Cancel this campaign? All messages will be removed.');">@csrf<button type="submit" class="rounded border border-red-600 bg-red-900/40 px-4 py-2 text-red-300 hover:bg-red-900/60">Cancel campaign</button></form>
        @endcan
    </div>
</div>

{{-- Tabs --}}
<nav class="mb-6 border-b border-slate-600">
    <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'overview']) }}" class="inline-block px-4 py-3 text-sm font-medium {{ $tab === 'overview' ? 'border-b-2 border-blue-500 text-blue-400' : 'text-slate-400 hover:text-slate-300' }}">Overview</a>
    <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'activity']) }}" class="inline-block px-4 py-3 text-sm font-medium {{ $tab === 'activity' ? 'border-b-2 border-blue-500 text-blue-400' : 'text-slate-400 hover:text-slate-300' }}">Activity</a>
    <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'users']) }}" class="inline-block px-4 py-3 text-sm font-medium {{ $tab === 'users' ? 'border-b-2 border-blue-500 text-blue-400' : 'text-slate-400 hover:text-slate-300' }}">Users</a>
</nav>

@if($tab === 'overview')
    {{-- Overview: Summary cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-4">
            <p class="text-sm text-slate-400">Users</p>
            <p class="text-2xl font-bold text-white">{{ $uniqueUsers }}</p>
        </div>
        <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-4">
            <p class="text-sm text-slate-400">Emails sent</p>
            <p class="text-2xl font-bold text-white">{{ $emailsSent }}</p>
        </div>
        <div class="rounded-lg border border-slate-600 bg-amber-900/20 p-4">
            <p class="text-sm text-slate-400">Users clicked</p>
            <p class="text-2xl font-bold text-amber-400">{{ $usersClicked }}</p>
            <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'activity', 'filter' => 'clicked']) }}" class="mt-1 block text-sm text-red-400 hover:underline">Go to list</a>
        </div>
        <div class="rounded-lg border border-slate-600 bg-amber-900/20 p-4">
            <p class="text-sm text-slate-400">Emails clicked</p>
            <p class="text-2xl font-bold text-amber-400">{{ $emailsClicked }}</p>
            <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'activity', 'filter' => 'clicked']) }}" class="mt-1 block text-sm text-red-400 hover:underline">Go to list</a>
        </div>
        <div class="rounded-lg border border-slate-600 bg-amber-900/20 p-4">
            <p class="text-sm text-slate-400">Total clicks</p>
            <p class="text-2xl font-bold text-amber-400">{{ $totalClicks }}</p>
            <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'activity', 'filter' => 'clicked']) }}" class="mt-1 block text-sm text-red-400 hover:underline">Go to list</a>
        </div>
    </div>

    @if($scheduled > 0 || $queued > 0 || $failed > 0)
        <div class="mb-6 flex flex-wrap gap-4 text-sm">
            @if($scheduled > 0)<span class="text-slate-400">Scheduled: {{ $scheduled }}</span>@endif
            @if($queued > 0)<span class="text-slate-400">Queued: {{ $queued }}</span>@endif
            @if($failed > 0)
                <span class="text-red-400">Failed: {{ $failed }}</span>
                @can('update', $campaign)
                    <form method="post" action="{{ route('admin.campaigns.retry-failed', $campaign) }}" class="inline">@csrf<button type="submit" class="rounded border border-amber-600 bg-amber-900/40 px-2 py-1 text-amber-300 hover:bg-amber-900/60">Retry failed</button></form>
                @endcan
            @endif
        </div>
    @endif

    {{-- Two columns: Top attack templates clicked, Users with most clicks --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
            <h2 class="mb-3 font-semibold text-slate-200">Top attack templates clicked</h2>
            @if($topAttacksClicked->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($topAttacksClicked as $attackId => $count)
                        <li class="flex justify-between text-slate-300">
                            <span>{{ $attackId ? ($attackNames[$attackId] ?? 'Unknown') : '—' }}</span>
                            <span class="font-medium text-amber-400">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-slate-500">No clicks yet.</p>
            @endif
        </div>
        <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
            <h2 class="mb-3 font-semibold text-slate-200">Users with most clicks</h2>
            @if($usersWithMostClicks->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($usersWithMostClicks as $email => $count)
                        <li class="flex justify-between text-slate-300">
                            <span>{{ $userNames[$email] ?? $email }}</span>
                            <span class="font-medium text-amber-400">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-slate-500">No clicks yet.</p>
            @endif
        </div>
    </div>

    {{-- Targets (collapsed under overview) --}}
    <div class="mt-8 rounded-lg border border-slate-600 bg-slate-800/50 p-6">
        <h2 class="font-semibold mb-2 text-slate-200">Targets</h2>
        <ul class="text-slate-300 space-y-1">
            @foreach($campaign->targets as $t)
                <li>{{ $t->target_type }}: {{ $t->target_identifier }} @if($t->display_name)({{ $t->display_name }})@endif</li>
            @endforeach
        </ul>
        @if($campaign->window_start && $campaign->window_end)
            <p class="mt-3 text-sm text-slate-400">Send window: {{ $campaign->window_start->format('M j, Y') }} – {{ $campaign->window_end->format('M j, Y') }} · Emails per recipient: {{ $campaign->emails_per_recipient ?? 1 }}</p>
        @endif
    </div>
@endif

@if($tab === 'activity')
    @php
        $activityList = request('filter') === 'clicked' ? $activities->where('event_type', 'clicked') : $activities;
    @endphp
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
        <div class="px-4 py-3 flex items-center justify-between bg-slate-800">
            <h2 class="font-semibold text-slate-200">Activity</h2>
            @if(request('filter') === 'clicked')
                <span class="text-sm text-slate-400">Showing click events only · <a href="{{ route('admin.campaigns.show', ['campaign' => $campaign, 'tab' => 'activity']) }}" class="text-blue-400 hover:underline">Show all</a></span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-600">
                <thead class="bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">User</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Email</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Sent date</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Category</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-600">
                    @forelse($activityList as $ev)
                        @php $msg = $ev->message; @endphp
                        <tr>
                            <td class="px-4 py-2 text-slate-200">{{ $msg->recipient_name ?: '—' }}</td>
                            <td class="px-4 py-2 text-slate-200">{{ $msg->recipient_email }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $msg->sent_at ? $msg->sent_at->format('n/j/Y') : '—' }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $msg->attack?->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $actionLabel = match($ev->event_type) {
                                        'clicked' => 'Clicked',
                                        'opened' => 'Opened',
                                        'reported' => 'Reported',
                                        'submitted' => 'Submitted',
                                        'sent' => 'Sent',
                                        'queued' => 'Queued',
                                        'failed' => 'Failed',
                                        'training_viewed' => 'Training viewed',
                                        default => $ev->event_type,
                                    };
                                @endphp
                                <span class="rounded bg-slate-700 px-2 py-0.5 text-xs text-slate-300">{{ $actionLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($tab === 'users')
    {{-- Users: Recipients with stats (from loaded messages/events) --}}
    @php
        $allEvents = $messages->flatMap->events;
        $userStats = $sentMessages->groupBy('recipient_email')->map(function ($msgs) use ($allEvents) {
            $first = $msgs->first();
            $ids = $msgs->pluck('id')->flip();
            $byMsg = $allEvents->groupBy('message_id');
            $clicked = $allEvents->where('event_type', 'clicked')->filter(fn ($e) => $ids->has($e->message_id))->count();
            $reported = $allEvents->where('event_type', 'reported')->filter(fn ($e) => $ids->has($e->message_id))->count();
            $submitted = $allEvents->where('event_type', 'submitted')->filter(fn ($e) => $ids->has($e->message_id))->count();
            return ['name' => $first->recipient_name, 'emails_sent' => $msgs->count(), 'clicked' => $clicked, 'reported' => $reported, 'submitted' => $submitted];
        });
    @endphp
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
        <h2 class="px-4 py-3 font-semibold text-slate-200 bg-slate-800">Users</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-600">
                <thead class="bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">User</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Email</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Emails sent</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Clicks</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Reported</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-slate-300">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-600">
                    @forelse($userStats as $email => $s)
                        <tr>
                            <td class="px-4 py-2 text-slate-200">{{ $s['name'] ?: '—' }}</td>
                            <td class="px-4 py-2 text-slate-200">{{ $email }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $s['emails_sent'] }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $s['clicked'] }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $s['reported'] }}</td>
                            <td class="px-4 py-2 text-slate-300">{{ $s['submitted'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<p class="mt-8"><a href="{{ route('admin.campaigns.index') }}" class="text-slate-400 hover:underline">← Back to campaigns</a></p>
@endsection

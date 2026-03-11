@extends('layouts.app')

@section('title', 'Edit '.$campaign->name)

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Edit campaign</h1>
    <p class="text-slate-400">{{ $campaign->name }}</p>
</div>

<form method="post" action="{{ route('admin.campaigns.update', $campaign) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-slate-300">Name</label>
        <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Template</label>
        <select name="template_id" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            @foreach($templates as $t)
                <option value="{{ $t->id }}" @selected(old('template_id', $campaign->template_id) == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Attack templates (optional)</label>
        <p class="text-sm text-slate-400 mb-2">Select one or more to mix phishing content per recipient.</p>
        @php $selectedAttackIds = old('attack_ids', $campaign->attacks->pluck('id')->toArray()); @endphp
        <div class="mt-1 space-y-2 max-h-48 overflow-y-auto rounded border border-slate-600 bg-slate-800 p-3">
            @forelse($attacks as $a)
                <label class="flex items-center gap-3 py-1 min-h-[2rem]">
                    <input type="checkbox" name="attack_ids[]" value="{{ $a->id }}" {{ in_array($a->id, $selectedAttackIds) ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                    <span class="text-slate-200">{{ $a->name }}</span>
                    <span class="text-xs text-slate-500 shrink-0">({{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }})</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No attack templates in Attack library.</p>
            @endforelse
        </div>
    </div>
    @if($canEditTargets)
        @php $firstTarget = $campaign->targets->first(); @endphp
        <div>
            <label class="block text-sm font-medium text-slate-300">Target type</label>
            <select name="target_type" required
                class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
                <option value="user" @selected(old('target_type', $firstTarget?->target_type) === 'user')>User (email)</option>
                <option value="group" @selected(old('target_type', $firstTarget?->target_type) === 'group')>Group (Google Workspace)</option>
                <option value="ou" @selected(old('target_type', $firstTarget?->target_type) === 'ou')>OU (organizational unit)</option>
                <option value="csv" @selected(old('target_type', $firstTarget?->target_type) === 'csv')>CSV</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Group: group address (e.g. staff@yourdomain.com). OU: org unit path (e.g. /Staff).</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300">Target (email, group address, or OU path)</label>
            <input type="text" name="target_identifier" value="{{ old('target_identifier', $firstTarget?->target_identifier) }}" required
                class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                placeholder="user@yourdomain.com or group@yourdomain.com">
            @error('target_identifier')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300">Display name (optional)</label>
            <input type="text" name="display_name" value="{{ old('display_name', $firstTarget?->display_name) }}"
                class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        </div>
    @else
        <div class="rounded-lg border border-amber-600/50 bg-amber-900/20 p-4">
            <h3 class="text-sm font-semibold text-amber-200 mb-2">Targets (locked)</h3>
            <p class="text-sm text-slate-300 mb-2">This campaign has already been launched. Targets cannot be changed here.</p>
            <ul class="text-slate-300 space-y-1 mb-2">
                @foreach($campaign->targets as $t)
                    <li>{{ $t->target_type }}: {{ $t->target_identifier }} @if($t->display_name)({{ $t->display_name }})@endif</li>
                @endforeach
            </ul>
            <p class="text-sm text-amber-200">To change targets, use <strong>Cancel campaign</strong> on the campaign page, then edit, re-approve, and launch again.</p>
        </div>
    @endif
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-2">Send window (optional)</h3>
        <p class="text-sm text-slate-400 mb-3">Set a date range to spread emails over time. Only applies when launching; leave blank to send one per recipient immediately.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300">From date</label>
                <input type="date" name="window_start" value="{{ old('window_start', $campaign->window_start?->format('Y-m-d')) }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300">To date</label>
                <input type="date" name="window_end" value="{{ old('window_end', $campaign->window_end?->format('Y-m-d')) }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-sm font-medium text-slate-300">Emails per recipient (during window)</label>
            <input type="number" name="emails_per_recipient" value="{{ old('emails_per_recipient', $campaign->emails_per_recipient ?? 1) }}" min="1" max="50"
                class="mt-1 w-24 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
        </div>
    </div>
    <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Update</button>
</form>
@endsection

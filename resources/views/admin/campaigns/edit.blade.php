@extends('layouts.app')

@section('title', 'Edit '.$campaign->name)

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Edit campaign</h1>
    <p class="text-slate-600">{{ $campaign->name }}</p>
</div>

<form method="post" action="{{ route('admin.campaigns.update', $campaign) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required class="mt-1 w-full rounded border-slate-300">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Template</label>
        <select name="template_id" required class="mt-1 w-full rounded border-slate-300">
            @foreach($templates as $t)
                <option value="{{ $t->id }}" @selected(old('template_id', $campaign->template_id) == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Attack templates (optional)</label>
        <p class="text-sm text-slate-500 mb-2">Select one or more to mix phishing content per recipient.</p>
        @php $selectedAttackIds = old('attack_ids', $campaign->attacks->pluck('id')->toArray()); @endphp
        <div class="mt-1 space-y-2 max-h-48 overflow-y-auto rounded border border-slate-300 p-3 bg-slate-50">
            @forelse($attacks as $a)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="attack_ids[]" value="{{ $a->id }}" {{ in_array($a->id, $selectedAttackIds) ? 'checked' : '' }} class="rounded border-slate-300">
                    <span>{{ $a->name }}</span>
                    <span class="text-xs text-slate-500">({{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }})</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No attack templates in Attack library.</p>
            @endforelse
        </div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Send window (optional)</h3>
        <p class="text-sm text-slate-600 mb-3">Set a date range to spread emails over time. Only applies when launching; leave blank to send one per recipient immediately.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">From date</label>
                <input type="date" name="window_start" value="{{ old('window_start', $campaign->window_start?->format('Y-m-d')) }}" class="mt-1 w-full rounded border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">To date</label>
                <input type="date" name="window_end" value="{{ old('window_end', $campaign->window_end?->format('Y-m-d')) }}" class="mt-1 w-full rounded border-slate-300">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-sm font-medium text-slate-700">Emails per recipient (during window)</label>
            <input type="number" name="emails_per_recipient" value="{{ old('emails_per_recipient', $campaign->emails_per_recipient ?? 1) }}" min="1" max="50" class="mt-1 w-full rounded border-slate-300 w-24">
        </div>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Update</button>
</form>
@endsection

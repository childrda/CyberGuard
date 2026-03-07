@extends('layouts.app')

@section('title', 'New campaign')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">New campaign</h1>
    <p class="text-slate-600">Create a phishing simulation campaign</p>
</div>

<form method="post" action="{{ route('admin.campaigns.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-slate-300">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Template</label>
        <select name="template_id" required class="mt-1 w-full rounded border-slate-300">
            @foreach($templates as $t)
                <option value="{{ $t->id }}" @selected(old('template_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        @error('template_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Attack templates (optional)</label>
        <p class="text-sm text-slate-500 mb-2">Select one or more to mix phishing content per recipient. If none selected, the template above is used for everyone.</p>
        <div class="mt-1 space-y-2 max-h-48 overflow-y-auto rounded border border-slate-300 p-3 bg-slate-50">
            @forelse($attacks as $a)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="attack_ids[]" value="{{ $a->id }}" {{ in_array($a->id, old('attack_ids', [])) ? 'checked' : '' }} class="rounded border-slate-300">
                    <span>{{ $a->name }}</span>
                    <span class="text-xs text-slate-500">({{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }})</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No attack templates. <a href="{{ route('admin.attacks.create') }}" class="underline">Create some</a> in Attack library.</p>
            @endforelse
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Target type</label>
        <select name="target_type" required class="mt-1 w-full rounded border-slate-300">
            <option value="user" @selected(old('target_type') === 'user')>User (email)</option>
            <option value="group" @selected(old('target_type') === 'group')>Group</option>
            <option value="csv" @selected(old('target_type') === 'csv')>CSV</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Target (email or group address)</label>
        <input type="text" name="target_identifier" value="{{ old('target_identifier') }}" required class="mt-1 w-full rounded border-slate-300" placeholder="user@yourdomain.com">
        @error('target_identifier')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Display name (optional)</label>
        <input type="text" name="display_name" value="{{ old('display_name') }}" class="mt-1 w-full rounded border-slate-300">
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Create campaign</button>
</form>
@endsection

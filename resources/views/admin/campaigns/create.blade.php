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

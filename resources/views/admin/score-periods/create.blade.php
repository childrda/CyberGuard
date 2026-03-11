@extends('layouts.app')

@section('title', 'New score period')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">New score period</h1>
    <p class="text-slate-400">e.g. Fall 2025, Spring term</p>
</div>

<form method="post" action="{{ route('admin.score-periods.store') }}" class="max-w-md space-y-4">
    @csrf
    <div>
        <label for="name" class="block text-sm font-medium text-slate-300">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required
            class="mt-1 w-full rounded border-slate-600 bg-slate-800 text-slate-200 px-3 py-2">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="slug" class="block text-sm font-medium text-slate-300">Slug (e.g. fall-2025)</label>
        <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
            pattern="[a-z0-9\-]+"
            class="mt-1 w-full rounded border-slate-600 bg-slate-800 text-slate-200 px-3 py-2">
        @error('slug')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-slate-300">Start date</label>
            <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                class="mt-1 w-full rounded border-slate-600 bg-slate-800 text-slate-200 px-3 py-2">
            @error('start_date')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-slate-300">End date</label>
            <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                class="mt-1 w-full rounded border-slate-600 bg-slate-800 text-slate-200 px-3 py-2">
            @error('end_date')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_current" id="is_current" value="1" {{ old('is_current') ? 'checked' : '' }}
            class="rounded border-slate-600 bg-slate-800 text-blue-600">
        <label for="is_current" class="text-sm text-slate-300">Set as current period</label>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white text-sm">Create</button>
        <a href="{{ route('admin.score-periods.index') }}" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-300 text-sm">Cancel</a>
    </div>
</form>
@endsection

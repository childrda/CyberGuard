@extends('layouts.app')

@section('title', 'New template')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">New template</h1>
</div>

<form method="post" action="{{ route('admin.templates.store') }}" class="max-w-2xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-slate-300">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Subject</label>
        <input type="text" name="subject" value="{{ old('subject') }}" required class="mt-1 w-full rounded border-slate-300">
        @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">HTML body</label>
        <textarea name="html_body" rows="10" required class="mt-1 w-full rounded border-slate-300">{{ old('html_body') }}</textarea>
        @error('html_body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Landing page type</label>
        <select name="landing_page_type" class="mt-1 w-full rounded border-slate-300">
            <option value="training">Training</option>
            <option value="credential_capture">Credential capture (training only)</option>
            <option value="custom">Custom</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Training page (optional)</label>
        <select name="training_page_id" class="mt-1 w-full rounded border-slate-300">
            <option value="">— None —</option>
            @foreach($landingPages as $lp)
                <option value="{{ $lp->id }}" @selected(old('training_page_id') == $lp->id)>{{ $lp->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Difficulty</label>
        <select name="difficulty" class="mt-1 w-full rounded border-slate-300">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Create template</button>
</form>
@endsection

@extends('layouts.app')

@section('title', 'Edit '.$template->name)

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Edit template</h1>
    <p class="text-slate-600">{{ $template->name }}</p>
</div>

<form method="post" action="{{ route('admin.templates.update', $template) }}" class="max-w-2xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-slate-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="mt-1 w-full rounded border-slate-300">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Subject</label>
        <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="mt-1 w-full rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">HTML body</label>
        <textarea name="html_body" rows="10" required class="mt-1 w-full rounded border-slate-300">{{ old('html_body', $template->html_body) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Landing page type</label>
        <select name="landing_page_type" class="mt-1 w-full rounded border-slate-300">
            <option value="training" @selected($template->landing_page_type === 'training')>Training</option>
            <option value="credential_capture" @selected($template->landing_page_type === 'credential_capture')>Credential capture</option>
            <option value="custom" @selected($template->landing_page_type === 'custom')>Custom</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Training page</label>
        <select name="training_page_id" class="mt-1 w-full rounded border-slate-300">
            <option value="">— None —</option>
            @foreach($landingPages as $lp)
                <option value="{{ $lp->id }}" @selected(old('training_page_id', $template->training_page_id) == $lp->id)>{{ $lp->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Difficulty</label>
        <select name="difficulty" class="mt-1 w-full rounded border-slate-300">
            <option value="low" @selected($template->difficulty === 'low')>Low</option>
            <option value="medium" @selected($template->difficulty === 'medium')>Medium</option>
            <option value="high" @selected($template->difficulty === 'high')>High</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Active</label>
        <input type="checkbox" name="active" value="1" @checked($template->active)>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Update</button>
</form>
@endsection

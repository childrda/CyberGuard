@extends('layouts.app')

@section('title', 'Edit ' . $attack->name)

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Edit attack template</h1>
    <p class="text-slate-600">Sent: {{ number_format($attack->times_sent) }} · Clicks: {{ number_format($attack->times_clicked) }}</p>
</div>

<form method="post" action="{{ route('admin.attacks.update', $attack) }}" class="max-w-2xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-slate-700">Name (internal)</label>
        <input type="text" name="name" value="{{ old('name', $attack->name) }}" required class="mt-1 w-full rounded border-slate-300">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Description (what this mimics)</label>
        <textarea name="description" rows="2" class="mt-1 w-full rounded border-slate-300">{{ old('description', $attack->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Subject</label>
        <input type="text" name="subject" value="{{ old('subject', $attack->subject) }}" required class="mt-1 w-full rounded border-slate-300">
        @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">From name</label>
            <input type="text" name="from_name" value="{{ old('from_name', $attack->from_name) }}" class="mt-1 w-full rounded border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">From email</label>
            <input type="email" name="from_email" value="{{ old('from_email', $attack->from_email) }}" class="mt-1 w-full rounded border-slate-300">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">HTML body</label>
        <textarea name="html_body" rows="10" required class="mt-1 w-full rounded border-slate-300 font-mono text-sm">{{ old('html_body', $attack->html_body) }}</textarea>
        @error('html_body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Plain text body (optional)</label>
        <textarea name="text_body" rows="4" class="mt-1 w-full rounded border-slate-300">{{ old('text_body', $attack->text_body) }}</textarea>
    </div>
    @php $difficultyLabels = [1 => 'Obvious', 2 => 'Easy to spot', 3 => 'Moderate', 4 => 'Convincing', 5 => 'Very realistic']; @endphp
    <div>
        <label class="block text-sm font-medium text-slate-700">Difficulty rating (1–5)</label>
        <select name="difficulty_rating" class="mt-1 w-full rounded border-slate-300">
            @foreach($difficultyLabels as $i => $label)
                <option value="{{ $i }}" @selected(old('difficulty_rating', $attack->difficulty_rating) == $i)>{{ $i }} – {{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Landing page type</label>
        <select name="landing_page_type" class="mt-1 w-full rounded border-slate-300">
            <option value="training" @selected(old('landing_page_type', $attack->landing_page_type) === 'training')>Training</option>
            <option value="credential_capture" @selected(old('landing_page_type', $attack->landing_page_type) === 'credential_capture')>Credential capture</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Training page (optional)</label>
        <select name="training_page_id" class="mt-1 w-full rounded border-slate-300">
            <option value="">— None —</option>
            @foreach($landingPages as $lp)
                <option value="{{ $lp->id }}" @selected(old('training_page_id', $attack->training_page_id) == $lp->id)>{{ $lp->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="inline-flex items-center"><input type="checkbox" name="active" value="1" {{ old('active', $attack->active) ? 'checked' : '' }} class="rounded border-slate-300"> Active</label>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Update</button>
</form>

<p class="mt-6"><a href="{{ route('admin.attacks.show', $attack) }}" class="text-slate-600 hover:underline">← Back to attack</a></p>
@endsection

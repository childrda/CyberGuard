@extends('layouts.app')

@section('title', $template->name)

@section('content')
<div class="mb-8 flex justify-between">
    <div>
        <h1 class="text-2xl font-bold">{{ $template->name }}</h1>
        <p class="text-slate-600">Difficulty: {{ $template->difficulty }}</p>
    </div>
    @can('update', $template)
        <a href="{{ route('admin.templates.edit', $template) }}" class="rounded border border-slate-300 px-4 py-2 text-slate-700">Edit</a>
    @endcan
</div>

<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="font-semibold mb-2">Subject</h2>
    <p>{{ $template->subject }}</p>
</div>
<div class="rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="font-semibold mb-2">HTML body (preview)</h2>
    <div class="prose max-w-none border rounded p-4 bg-slate-50">{{ $template->html_body ? Str::limit(strip_tags($template->html_body), 500) : '-' }}</div>
</div>
@endsection

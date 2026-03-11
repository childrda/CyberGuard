@extends('layouts.app')

@section('title', 'Preview: ' . $attack->name)

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-white">Preview: {{ $attack->name }}</h1>
        <p class="text-slate-400 mt-1">Sample data used for placeholders. No email is sent.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.attacks.edit', $attack) }}" class="rounded border border-slate-500 bg-slate-700 px-4 py-2 text-slate-200 hover:bg-slate-600">Edit</a>
        <a href="{{ route('admin.attacks.show', $attack) }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-500">Back to attack</a>
    </div>
</div>

<div class="rounded-lg border border-slate-600 bg-slate-800 overflow-hidden mb-6">
    <div class="p-3 border-b border-slate-600 text-sm text-slate-400">
        <strong>Subject:</strong> {{ $subject }}
    </div>
    <div class="p-4">
        <div class="prose prose-invert max-w-none text-slate-200">
            {!! $html_body !!}
        </div>
    </div>
</div>

<details class="rounded-lg border border-slate-600 bg-slate-800 p-4">
    <summary class="cursor-pointer text-slate-300 font-medium">Sample placeholder values used</summary>
    <pre class="mt-2 text-xs text-slate-400 overflow-auto">{{ json_encode($sample_context, JSON_PRETTY_PRINT) }}</pre>
</details>
@endsection

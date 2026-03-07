@extends('layouts.app')

@section('title', $attack->name)

@section('content')
<div class="mb-8 flex justify-between">
    <div>
        <h1 class="text-2xl font-bold">{{ $attack->name }}</h1>
        <p class="text-slate-600">Difficulty: {{ $attack->difficulty_rating }} – {{ $attack->difficultyLabel() }} · Sent: {{ number_format($attack->times_sent) }} · Clicks: {{ number_format($attack->times_clicked) }}</p>
    </div>
    @can('update', $attack)
        <a href="{{ route('admin.attacks.edit', $attack) }}" class="rounded border border-slate-300 px-4 py-2 text-slate-700">Edit</a>
    @endcan
</div>

@if($attack->description)
<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-2">Description (what this mimics)</h2>
    <p class="text-slate-700">{{ $attack->description }}</p>
</div>
@endif

<div class="rounded-lg border border-slate-200 bg-white p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-2">Subject</h2>
    <p>{{ $attack->subject }}</p>
    @if($attack->from_name || $attack->from_email)
        <p class="mt-2 text-sm text-slate-500">From: {{ $attack->from_name }} {{ $attack->from_email ? '&lt;' . e($attack->from_email) . '&gt;' : '' }}</p>
    @endif
</div>

<div class="rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-2">HTML body (preview)</h2>
    <div class="prose max-w-none border rounded p-4 bg-slate-50 text-sm">{{ Str::limit(strip_tags($attack->html_body), 800) }}</div>
</div>

<p class="mt-6"><a href="{{ route('admin.attacks.index') }}" class="text-slate-600 hover:underline">← Back to attack library</a></p>
@endsection

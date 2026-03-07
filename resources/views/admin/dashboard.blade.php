@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="text-slate-600">Phishing awareness overview</p>
</div>

<form method="get" class="mb-6 flex gap-4 items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">From</label>
        <input type="date" name="from" value="{{ $dateFrom }}" class="mt-1 rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">To</label>
        <input type="date" name="to" value="{{ $dateTo }}" class="mt-1 rounded border-slate-300">
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Apply</button>
</form>

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6 mb-8">
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Active campaigns</div>
        <div class="text-2xl font-bold">{{ $activeCampaigns }}</div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Sent</div>
        <div class="text-2xl font-bold">{{ $sentCount }}</div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Delivered</div>
        <div class="text-2xl font-bold">{{ $deliveredCount }}</div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Unique clicks</div>
        <div class="text-2xl font-bold">{{ $uniqueClicks }}</div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Reports</div>
        <div class="text-2xl font-bold">{{ $reportCount }}</div>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-sm text-slate-500">Submissions</div>
        <div class="text-2xl font-bold">{{ $submissionCount }}</div>
    </div>
</div>

<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-4">Recent campaigns</h2>
        <ul class="divide-y divide-slate-200">
            @forelse($recentCampaigns as $c)
                <li class="py-2 flex justify-between items-center">
                    <a href="{{ route('admin.campaigns.show', $c) }}" class="text-slate-800 hover:underline">{{ $c->name }}</a>
                    <span class="rounded px-2 py-0.5 text-xs font-medium
                        @if($c->status === 'completed') bg-green-100 text-green-800
                        @elseif($c->status === 'sending') bg-blue-100 text-blue-800
                        @elseif($c->status === 'draft') bg-slate-100 text-slate-800
                        @else bg-amber-100 text-amber-800
                        @endif">{{ $c->status }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No campaigns yet.</li>
            @endforelse
        </ul>
        <a href="{{ route('admin.campaigns.index') }}" class="mt-2 inline-block text-sm text-slate-600 hover:underline">View all</a>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold mb-4">Recent reports</h2>
        <ul class="divide-y divide-slate-200">
            @forelse($recentReports as $r)
                <li class="py-2 flex justify-between items-center">
                    <a href="{{ route('admin.reports.show', $r) }}" class="text-slate-800 hover:underline truncate max-w-[200px]">{{ $r->subject ?? 'No subject' }}</a>
                    <span class="text-xs text-slate-500">{{ $r->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-500">No reports yet.</li>
            @endforelse
        </ul>
        <a href="{{ route('admin.reports.index') }}" class="mt-2 inline-block text-sm text-slate-600 hover:underline">View all</a>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold">Campaigns</h1>
        <p class="text-slate-600">Phishing simulation campaigns</p>
    </div>
    @can('create', \App\Models\PhishingCampaign::class)
        <a href="{{ route('admin.campaigns.create') }}" class="rounded bg-slate-800 px-4 py-2 text-white">New campaign</a>
    @endcan
</div>

<form method="get" class="mb-4 flex gap-3 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-500">Status</label>
        <select name="status" class="mt-1 rounded border border-slate-300 bg-white px-3 py-2 text-slate-800 text-sm">
            <option value="">All</option>
            @foreach(['draft','approved','sending','completed','cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-500">Per page</label>
        <select name="per_page" class="mt-1 rounded border border-slate-300 bg-white px-3 py-2 text-slate-800 text-sm">
            @foreach(($allowedPerPage ?? [10,20,40,100]) as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', $perPage ?? 20) === (int) $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-700 px-4 py-2 text-white text-sm hover:bg-slate-600">Filter</button>
    @if(request()->hasAny(['status','per_page']))
        <a href="{{ route('admin.campaigns.index') }}" class="text-slate-600 text-sm hover:underline">Clear</a>
    @endif
</form>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Template</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Created</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($campaigns as $c)
                <tr>
                    <td class="px-4 py-3"><a href="{{ route('admin.campaigns.show', $c) }}" class="text-slate-800 hover:underline">{{ $c->name }}</a></td>
                    <td class="px-4 py-3 text-slate-600">{{ $c->template->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded px-2 py-0.5 text-xs font-medium
                            @if($c->status === 'completed') bg-green-100 text-green-800
                            @elseif($c->status === 'sending') bg-blue-100 text-blue-800
                            @elseif($c->status === 'draft') bg-slate-100 text-slate-800
                            @elseif($c->status === 'approved') bg-emerald-100 text-emerald-800
                            @else bg-amber-100 text-amber-800
                            @endif">{{ $c->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $c->created_at->toDateString() }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.campaigns.show', $c) }}" class="text-slate-600 hover:underline">View</a>
                        @can('update', $c)
                            <a href="{{ route('admin.campaigns.edit', $c) }}" class="ml-2 text-slate-600 hover:underline">Edit</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $campaigns->appends(request()->query())->links() }}</div>
@endsection

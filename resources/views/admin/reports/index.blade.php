@extends('layouts.app')

@section('title', 'Reported messages')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Reported messages</h1>
    <p class="text-slate-400">Simulation reports and real suspicious emails</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-300">Type</label>
        <select name="type" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            <option value="">All</option>
            <option value="simulation" @selected(request('type') === 'simulation')>Simulation</option>
            <option value="real" @selected(request('type') === 'real')>Real</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Status</label>
        <select name="status" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            <option value="">All</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="analyst_confirmed_real" @selected(request('status') === 'analyst_confirmed_real')>Confirmed phishing</option>
            <option value="false_positive" @selected(request('status') === 'false_positive')>False positive</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Per page</label>
        <select name="per_page" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
            @foreach(($allowedPerPage ?? [10,20,40,100]) as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', $perPage ?? 20) === (int) $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-200">Filter</button>
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Reporter</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Subject</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">From</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Type</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Date</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-200">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @foreach($reports as $r)
                <tr>
                    <td class="px-4 py-3 text-slate-200">{{ $r->reporter_email }}</td>
                    <td class="px-4 py-3 text-slate-200 truncate max-w-[200px]">{{ $r->subject ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-200">{{ $r->from_address ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if($r->phishing_message_id)
                            <span class="rounded px-2 py-0.5 text-xs bg-green-600/30 text-green-300">Simulation</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs bg-amber-600/30 text-amber-300">Real</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($r->analyst_status === 'analyst_confirmed_real')
                            <span class="rounded px-2 py-0.5 text-xs bg-red-600/30 text-red-300">Phishing</span>
                        @elseif($r->analyst_status === 'false_positive')
                            <span class="rounded px-2 py-0.5 text-xs bg-slate-600/50 text-slate-300">False +</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs bg-slate-600/50 text-slate-300">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-300">{{ $r->created_at->toDateString() }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.reports.show', $r) }}" class="text-slate-300 hover:text-white hover:underline">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4 text-slate-300">{{ $reports->appends(request()->query())->links() }}</div>
@endsection

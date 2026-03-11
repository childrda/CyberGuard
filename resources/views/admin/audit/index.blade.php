@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Audit log</h1>
    <p class="text-slate-400">Immutable audit trail</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-300">Action</label>
        <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. campaign_launched" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">User ID</label>
        <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="User id" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Correlation ID</label>
        <input type="text" name="correlation_id" value="{{ request('correlation_id') }}" placeholder="Trace report → remediation" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
    </div>
    <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-200">Filter</button>
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Time</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Action</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">User</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Auditable</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Correlation ID</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @foreach($logs as $log)
                <tr>
                    <td class="px-4 py-3 text-slate-300">{{ $log->created_at->toDateTimeString() }}</td>
                    <td class="px-4 py-3 text-slate-200">{{ $log->action }}</td>
                    <td class="px-4 py-3 text-slate-200">{{ $log->user?->email ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-200">{{ $log->auditable_type ? $log->auditable_type.'#'.$log->auditable_id : '-' }}</td>
                    <td class="px-4 py-3 text-slate-300 text-xs font-mono">{{ $log->correlation_id ? substr($log->correlation_id, 0, 8).'…' : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4 text-slate-300">{{ $logs->links() }}</div>
@endsection

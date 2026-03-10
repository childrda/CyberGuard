@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Audit log</h1>
    <p class="text-slate-600">Immutable audit trail</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">Action</label>
        <input type="text" name="action" value="{{ request('action') }}" placeholder="e.g. campaign_launched" class="mt-1 rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">User ID</label>
        <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="User id" class="mt-1 rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Correlation ID</label>
        <input type="text" name="correlation_id" value="{{ request('correlation_id') }}" placeholder="Trace report → remediation" class="mt-1 rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 rounded border-slate-300">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 rounded border-slate-300">
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Filter</button>
</form>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Time</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Action</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">User</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Auditable</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Correlation ID</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($logs as $log)
                <tr>
                    <td class="px-4 py-3 text-slate-600">{{ $log->created_at->toDateTimeString() }}</td>
                    <td class="px-4 py-3">{{ $log->action }}</td>
                    <td class="px-4 py-3">{{ $log->user?->email ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $log->auditable_type ? $log->auditable_type.'#'.$log->auditable_id : '-' }}</td>
                    <td class="px-4 py-3 text-xs font-mono">{{ $log->correlation_id ? substr($log->correlation_id, 0, 8).'…' : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection

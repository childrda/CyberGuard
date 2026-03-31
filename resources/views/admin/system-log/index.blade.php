@extends('layouts.app')

@section('title', 'System log')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">System log</h1>
    <p class="text-slate-400">Recent errors (e.g. mail failures) for administrators</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-300">Per page</label>
        <select name="per_page" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
            @foreach(($allowedPerPage ?? [10,20,40,100]) as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', $perPage ?? 20) === (int) $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-200 text-sm">Apply</button>
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Time</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Type</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Message</th>
                @if(auth()->user()?->isPlatformAdmin())
                    <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Tenant</th>
                @endif
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Context</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @forelse($logs as $log)
                <tr>
                    <td class="px-4 py-3 text-slate-300 text-sm">{{ $log->created_at->toDateTimeString() }}</td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-medium bg-red-600/30 text-red-300">{{ $log->type }}</span></td>
                    <td class="px-4 py-3 text-slate-200">{{ $log->message }}</td>
                    @if(auth()->user()?->isPlatformAdmin())
                        <td class="px-4 py-3 text-slate-300">{{ $log->tenant?->name ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-3 text-slate-400 text-xs font-mono max-w-xs truncate">{{ $log->context ? json_encode($log->context) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()?->isPlatformAdmin() ? 5 : 4 }}" class="px-4 py-8 text-center text-slate-400">No log entries.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 text-slate-300">{{ $logs->appends(request()->query())->links() }}</div>
@endsection

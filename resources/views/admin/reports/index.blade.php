@extends('layouts.app')

@section('title', 'Reported messages')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Reported messages</h1>
    <p class="text-slate-600">Simulation reports and real suspicious emails</p>
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <select name="type" class="mt-1 rounded border-slate-300">
            <option value="">All</option>
            <option value="simulation" @selected(request('type') === 'simulation')>Simulation</option>
            <option value="real" @selected(request('type') === 'real')>Real</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Status</label>
        <select name="status" class="mt-1 rounded border-slate-300">
            <option value="">All</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="analyst_confirmed_real" @selected(request('status') === 'analyst_confirmed_real')>Confirmed phishing</option>
            <option value="false_positive" @selected(request('status') === 'false_positive')>False positive</option>
        </select>
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
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Reporter</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Subject</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">From</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Type</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Status</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Date</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($reports as $r)
                <tr>
                    <td class="px-4 py-3">{{ $r->reporter_email }}</td>
                    <td class="px-4 py-3 truncate max-w-[200px]">{{ $r->subject ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $r->from_address ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @if($r->phishing_message_id)
                            <span class="rounded px-2 py-0.5 text-xs bg-green-100 text-green-800">Simulation</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs bg-amber-100 text-amber-800">Real</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($r->analyst_status === 'analyst_confirmed_real')
                            <span class="rounded px-2 py-0.5 text-xs bg-red-100 text-red-800">Phishing</span>
                        @elseif($r->analyst_status === 'false_positive')
                            <span class="rounded px-2 py-0.5 text-xs bg-slate-100 text-slate-600">False +</span>
                        @else
                            <span class="rounded px-2 py-0.5 text-xs bg-slate-100 text-slate-500">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->created_at->toDateString() }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.reports.show', $r) }}" class="text-slate-600 hover:underline">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $reports->links() }}</div>
@endsection

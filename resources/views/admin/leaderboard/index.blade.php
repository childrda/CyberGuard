@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Leaderboard</h1>
    <p class="text-slate-600">Shield points by user (monthly)</p>
</div>

<form method="get" class="mb-6 flex gap-4 items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">Month</label>
        <input type="month" name="month" value="{{ $month }}" class="mt-1 rounded border-slate-300 text-sm">
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white text-sm">Apply</button>
</form>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Rank</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">User</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Points</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($leaderboard as $index => $row)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $index + 1 }}</td>
                    <td class="px-4 py-3">{{ $row->user_identifier }}</td>
                    <td class="px-4 py-3">{{ $row->total_points }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

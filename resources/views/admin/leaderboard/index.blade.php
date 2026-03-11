@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Leaderboard</h1>
    <p class="text-slate-400">Shield points by {{ $scope === 'department' ? 'department' : ($scope === 'ou' ? 'OU' : 'user') }} — {{ $periodLabel }}</p>
</div>

<form method="get" class="mb-6 flex flex-wrap gap-4 items-end">
    <div class="flex gap-2 items-center">
        <span class="text-sm text-slate-400">Scope:</span>
        <a href="{{ route('admin.leaderboard.index', ['scope' => 'tenant', 'period' => $scorePeriodId]) }}" class="rounded px-3 py-1.5 text-sm {{ $scope === 'tenant' ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">By user</a>
        <a href="{{ route('admin.leaderboard.index', ['scope' => 'department', 'period' => $scorePeriodId]) }}" class="rounded px-3 py-1.5 text-sm {{ $scope === 'department' ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">By department</a>
        <a href="{{ route('admin.leaderboard.index', ['scope' => 'ou', 'period' => $scorePeriodId]) }}" class="rounded px-3 py-1.5 text-sm {{ $scope === 'ou' ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }}">By OU</a>
    </div>
    @if($periods->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-slate-400">Score period</label>
            <select name="period" onchange="this.form.submit()" class="mt-1 rounded border-slate-600 bg-slate-800 text-slate-200 text-sm px-3 py-2">
                <option value="">All time</option>
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" @selected($scorePeriodId == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <input type="hidden" name="scope" value="{{ $scope }}">
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Rank</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">{{ $scope === 'department' ? 'Department' : ($scope === 'ou' ? 'OU' : 'User') }}</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Points</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @foreach($leaderboard as $row)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-200">{{ $row['rank'] ?? 0 }}</td>
                    <td class="px-4 py-3 text-slate-200">
                        {{ $row['user_identifier'] ?? $row['department'] ?? $row['ou'] ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-slate-200">{{ $row['total_points'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(empty($leaderboard) && \App\Models\Tenant::currentId())
    <p class="mt-4 text-slate-500 text-sm">No points in this period yet. Points are recorded when users report simulations, complete training, or interact with simulations.</p>
@endif
@endsection

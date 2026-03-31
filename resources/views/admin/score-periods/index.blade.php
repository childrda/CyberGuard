@extends('layouts.app')

@section('title', 'Score periods')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold">Score periods</h1>
        <p class="text-slate-400">Terms or semesters for leaderboard and points</p>
    </div>
    <a href="{{ route('admin.score-periods.create') }}" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-white text-sm">New period</a>
</div>

@if(session('success'))
    <p class="mb-4 text-sm text-green-400">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p class="mb-4 text-sm text-amber-400">{{ session('error') }}</p>
@endif
@if(isset($gamificationEnabled) && !$gamificationEnabled)
    <div class="mb-4 rounded-lg border border-slate-600 bg-slate-800/50 p-4 text-slate-400 text-sm">
        Gamification is disabled for this tenant. Score periods are not used for points or leaderboard until a platform administrator enables gamification in Settings → Edit tenant.
    </div>
@endif

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-300">Per page</label>
        <select name="per_page" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
            @foreach(($allowedPerPage ?? [10,20,40,100]) as $size)
                <option value="{{ $size }}" @selected((int) request('per_page', $perPage ?? 20) === (int) $size)>{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-white text-sm">Apply</button>
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Slug</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Start</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">End</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-300">Current</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-300">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @forelse($periods as $p)
                <tr>
                    <td class="px-4 py-3 text-slate-200">{{ $p->name }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $p->slug }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $p->start_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-slate-400">{{ $p->end_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        @if($p->is_current)
                            <span class="rounded px-2 py-0.5 text-xs font-medium bg-green-600/30 text-green-300">Current</span>
                        @else
                            <form method="post" action="{{ route('admin.score-periods.set-current', $p->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-slate-500 hover:text-blue-400 text-sm">Set current</button>
                            </form>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">No score periods yet. Create one to scope leaderboards by term.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4 text-slate-300">{{ $periods->appends(request()->query())->links() }}</div>
@endsection

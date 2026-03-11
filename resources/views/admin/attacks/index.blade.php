@extends('layouts.app')

@section('title', 'Attack library')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-white">Attack library</h1>
        <p class="text-slate-400">Phishing message templates; mix and attach to campaigns for variety</p>
    </div>
    @can('create', \App\Models\PhishingAttack::class)
        <a href="{{ route('admin.attacks.create') }}" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-200">New attack template</a>
    @endcan
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-300">Difficulty</label>
        <select name="difficulty" class="mt-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
            <option value="">All</option>
            <option value="1" @selected(request('difficulty') === '1')>1 – Obvious</option>
            <option value="2" @selected(request('difficulty') === '2')>2 – Easy to spot</option>
            <option value="3" @selected(request('difficulty') === '3')>3 – Moderate</option>
            <option value="4" @selected(request('difficulty') === '4')>4 – Convincing</option>
            <option value="5" @selected(request('difficulty') === '5')>5 – Very realistic</option>
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-200 text-sm">Filter</button>
</form>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 overflow-hidden">
    <table class="min-w-full divide-y divide-slate-600">
        <thead class="bg-slate-800">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Difficulty</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Sent</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-200">Clicks</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-200">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-600">
            @foreach($attacks as $a)
                <tr>
                    <td class="px-4 py-3"><a href="{{ route('admin.attacks.show', $a) }}" class="text-slate-200 hover:text-white hover:underline">{{ $a->name }}</a></td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-medium text-slate-200 {{ $a->difficulty_rating <= 2 ? 'bg-green-600/30 text-green-300' : ($a->difficulty_rating >= 4 ? 'bg-amber-600/30 text-amber-300' : 'bg-slate-600/50 text-slate-300') }}">{{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }}</span></td>
                    <td class="px-4 py-3 text-slate-300">{{ number_format($a->times_sent) }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ number_format($a->times_clicked) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.attacks.show', $a) }}" class="text-slate-300 hover:text-white hover:underline">View</a>
                        @can('update', $a)
                            <a href="{{ route('admin.attacks.edit', $a) }}" class="ml-2 text-slate-300 hover:text-white hover:underline">Edit</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4 text-slate-300">{{ $attacks->links() }}</div>
@endsection

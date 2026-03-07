@extends('layouts.app')

@section('title', 'Attack library')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold">Attack library</h1>
        <p class="text-slate-600">Phishing message templates; mix and attach to campaigns for variety</p>
    </div>
    @can('create', \App\Models\PhishingAttack::class)
        <a href="{{ route('admin.attacks.create') }}" class="rounded bg-slate-800 px-4 py-2 text-white">New attack template</a>
    @endcan
</div>

<form method="get" class="mb-6 flex gap-4 flex-wrap items-end">
    <div>
        <label class="block text-sm font-medium text-slate-700">Difficulty</label>
        <select name="difficulty" class="mt-1 rounded border-slate-300 text-sm">
            <option value="">All</option>
            <option value="1" @selected(request('difficulty') === '1')>1 – Obvious</option>
            <option value="2" @selected(request('difficulty') === '2')>2 – Easy to spot</option>
            <option value="3" @selected(request('difficulty') === '3')>3 – Moderate</option>
            <option value="4" @selected(request('difficulty') === '4')>4 – Convincing</option>
            <option value="5" @selected(request('difficulty') === '5')>5 – Very realistic</option>
        </select>
    </div>
    <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white text-sm">Filter</button>
</form>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Difficulty</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Sent</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Clicks</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($attacks as $a)
                <tr>
                    <td class="px-4 py-3"><a href="{{ route('admin.attacks.show', $a) }}" class="text-slate-800 hover:underline">{{ $a->name }}</a></td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs font-medium {{ $a->difficulty_rating <= 2 ? 'bg-green-100 text-green-800' : ($a->difficulty_rating >= 4 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">{{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }}</span></td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format($a->times_sent) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ number_format($a->times_clicked) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.attacks.show', $a) }}" class="text-slate-600 hover:underline">View</a>
                        @can('update', $a)
                            <a href="{{ route('admin.attacks.edit', $a) }}" class="ml-2 text-slate-600 hover:underline">Edit</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $attacks->links() }}</div>
@endsection

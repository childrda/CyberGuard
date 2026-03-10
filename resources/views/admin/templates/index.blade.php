@extends('layouts.app')

@section('title', 'Templates')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold">Templates</h1>
        <p class="text-slate-600">Phishing email templates</p>
    </div>
    @can('create', \App\Models\PhishingTemplate::class)
        <a href="{{ route('admin.templates.create') }}" class="rounded bg-slate-800 px-4 py-2 text-white">New template</a>
    @endcan
</div>

<div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Name</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Subject</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Difficulty</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($templates as $t)
                <tr>
                    <td class="px-4 py-3"><a href="{{ route('admin.templates.show', $t) }}" class="text-slate-800 hover:underline">{{ $t->name }}</a></td>
                    <td class="px-4 py-3 text-slate-600">{{ Str::limit($t->subject, 50) }}</td>
                    <td class="px-4 py-3"><span class="rounded px-2 py-0.5 text-xs bg-slate-100">{{ $t->difficulty }}</span></td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.templates.show', $t) }}" class="text-slate-600 hover:underline">View</a>
                        @can('update', $t)
                            <a href="{{ route('admin.templates.edit', $t) }}" class="ml-2 text-slate-600 hover:underline">Edit</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $templates->links() }}</div>
@endsection

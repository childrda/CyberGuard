@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="mb-8 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold text-white">Settings</h1>
        <p class="text-slate-400">Tenant and platform configuration</p>
    </div>
    @if(auth()->user()?->isPlatformAdmin())
        <a href="{{ route('admin.tenants.create') }}" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white text-sm">Add tenant</a>
    @endif
</div>

@if(session('success'))
    <p class="mb-4 text-sm text-green-400">{{ session('success') }}</p>
@endif

@if($tenant)
<div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6 max-w-2xl flex justify-between items-start">
    <div>
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-4">Current tenant</h2>
        <dl class="grid gap-3 text-sm">
            <div><dt class="text-slate-500">Name</dt><dd class="font-medium text-slate-200">{{ $tenant->name }}</dd></div>
            <div><dt class="text-slate-500">Domain</dt><dd class="text-slate-200">{{ $tenant->domain }}</dd></div>
            <div><dt class="text-slate-500">Remediation policy</dt><dd><span class="rounded px-2 py-0.5 text-xs bg-slate-600/50 text-slate-200">{{ $tenant->remediation_policy }}</span></dd></div>
        </dl>
    </div>
    @if(auth()->user()?->isPlatformAdmin())
        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-300 hover:bg-slate-700 text-sm shrink-0">Edit tenant</a>
    @endif
</div>
@else
<div class="rounded-lg border border-amber-600/50 bg-amber-500/10 p-4 text-amber-200 text-sm">
    No tenant selected. Use the tenant switcher in the left sidebar to select a tenant.
</div>
@endif

@if(auth()->user()?->isPlatformAdmin())
<div class="mt-8 rounded-lg border border-slate-600 bg-slate-800/50 p-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-4">All tenants</h2>
    <ul class="space-y-2 text-sm text-slate-300">
        @forelse($tenants as $t)
            <li class="flex items-center gap-2">
                {{ $t->name }} ({{ $t->domain }}) — <span class="text-slate-500">{{ $t->remediation_policy }}</span>
                <a href="{{ route('admin.tenants.edit', $t) }}" class="text-blue-400 hover:underline">Edit</a>
            </li>
        @empty
            <li class="text-slate-500">No tenants yet. <a href="{{ route('admin.tenants.create') }}" class="text-blue-400 hover:underline">Add one</a>.</li>
        @endforelse
    </ul>
</div>
@endif
@endsection

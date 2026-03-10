@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Settings</h1>
    <p class="text-slate-600">Tenant and platform configuration</p>
</div>

@if($tenant)
<div class="rounded-lg border border-slate-200 bg-white p-6 max-w-2xl">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Current tenant</h2>
    <dl class="grid gap-3 text-sm">
        <div><dt class="text-slate-500">Name</dt><dd class="font-medium">{{ $tenant->name }}</dd></div>
        <div><dt class="text-slate-500">Domain</dt><dd>{{ $tenant->domain }}</dd></div>
        <div><dt class="text-slate-500">Remediation policy</dt><dd><span class="rounded px-2 py-0.5 text-xs bg-slate-100">{{ $tenant->remediation_policy }}</span></dd></div>
    </dl>
</div>
@else
<div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
    No tenant selected. Use the tenant switcher in the left sidebar to select a tenant.
</div>
@endif

@if(auth()->user()?->isPlatformAdmin() && $tenants->isNotEmpty())
<div class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">All tenants</h2>
    <ul class="space-y-2 text-sm">
        @foreach($tenants as $t)
            <li>{{ $t->name }} ({{ $t->domain }}) — {{ $t->remediation_policy }}</li>
        @endforeach
    </ul>
</div>
@endif
@endsection

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
@if(session('status') === 'password-updated')
    <p class="mb-4 text-sm text-green-400">Your password has been updated.</p>
@endif

<div class="mb-8 rounded-lg border border-slate-600 bg-slate-800/50 p-6 max-w-2xl">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">Your password</h2>
    <p class="text-sm text-slate-500 mb-4">Change the password for <span class="text-slate-300">{{ auth()->user()->email }}</span>. Use a strong password.</p>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4 max-w-md">
        @csrf
        @method('PUT')
        <div>
            <label for="current_password" class="block text-sm font-medium text-slate-300">Current password</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
            @error('current_password')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-300">New password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
            @error('password')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-300">Confirm new password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
        </div>
        <button type="submit" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-white text-sm">Update password</button>
    </form>
    <p class="mt-4 text-xs text-slate-500">If you do not remember your current password, sign out and use <a href="{{ route('password.request') }}" class="text-blue-400 hover:underline">Forgot password</a> on the login page (email must be configured on the server).</p>
</div>

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
    @if(auth()->user()?->isPlatformAdmin() || auth()->user()?->tenant_id === $tenant->id)
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

@extends('layouts.app')

@section('title', 'Edit '.$tenant->name)

@section('content')
<div class="mb-8 flex justify-between items-start">
    <div>
        <h1 class="text-2xl font-bold text-white">Edit tenant</h1>
        <p class="text-slate-400">{{ $tenant->name }}</p>
    </div>
    <a href="{{ route('admin.settings.index') }}" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-300 hover:bg-slate-700">Back to Settings</a>
</div>

@if(session('success'))
    <p class="mb-4 text-sm text-green-400">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p class="mb-4 text-sm text-red-400">{{ session('error') }}</p>
@endif

<div class="max-w-2xl space-y-8">
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-4">Tenant settings</h2>
        <form method="post" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
                @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="domain" class="block text-sm font-medium text-slate-300">Domain</label>
                <input type="text" name="domain" id="domain" value="{{ old('domain', $tenant->domain) }}" required
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="e.g. lcps.k12.va.us">
                <p class="mt-1 text-xs text-slate-500">Primary email domain for this tenant.</p>
                @error('domain')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-slate-300">Slug</label>
                <input type="text" name="slug" id="slug" value="{{ old('slug', $tenant->slug) }}" required
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="e.g. lcps">
                <p class="mt-1 text-xs text-slate-500">Short identifier for URLs/API. Lowercase letters, numbers, hyphens only.</p>
                @error('slug')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="remediation_policy" class="block text-sm font-medium text-slate-300">Remediation policy</label>
                <select name="remediation_policy" id="remediation_policy"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
                    <option value="report_only" @selected(old('remediation_policy', $tenant->remediation_policy) === 'report_only')>Report only (no mailbox removal)</option>
                    <option value="analyst_approval_required" @selected(old('remediation_policy', $tenant->remediation_policy) === 'analyst_approval_required')>Analyst approval required</option>
                    <option value="auto_remove_confirmed_phish" @selected(old('remediation_policy', $tenant->remediation_policy) === 'auto_remove_confirmed_phish')>Auto-remove confirmed phish</option>
                </select>
                @error('remediation_policy')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" id="active" value="1" {{ old('active', $tenant->active) ? 'checked' : '' }}
                    class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                <label for="active" class="text-sm text-slate-300">Tenant active (inactive tenants cannot be selected)</label>
            </div>
            @error('active')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            <div class="flex gap-2">
                <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Save tenant</button>
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
        <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">Users in this tenant</h2>
        <p class="text-sm text-slate-400 mb-4">These users can sign in and work within this tenant. Add an admin by email and role below.</p>
        <ul class="mb-6 space-y-2 text-sm text-slate-300">
            @forelse($users as $u)
                <li>{{ $u->name }} &lt;{{ $u->email }}&gt; — <span class="rounded px-2 py-0.5 text-xs bg-slate-600/50 text-slate-200">{{ $u->roles->pluck('name')->join(', ') }}</span></li>
            @empty
                <li class="text-slate-500">No users in this tenant yet. Add one below.</li>
            @endforelse
        </ul>
        <h3 class="text-sm font-medium text-slate-300 mb-2">Add user to this tenant</h3>
        <form method="post" action="{{ route('admin.tenants.add-user', $tenant) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[200px]">
                <label for="email" class="block text-xs font-medium text-slate-400">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm"
                    placeholder="admin@{{ $tenant->domain }}">
                @error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="min-w-[140px]">
                <label for="user_name" class="block text-xs font-medium text-slate-400">Name (optional)</label>
                <input type="text" name="user_name" id="user_name" value="{{ old('user_name') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm"
                    placeholder="Display name">
            </div>
            <div class="min-w-[160px]">
                <label for="role" class="block text-xs font-medium text-slate-400">Role</label>
                <select name="role" id="role" required
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm">
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}" @selected(old('role') === $r->name)>{{ $r->label ?? $r->name }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded bg-green-600 hover:bg-green-500 px-4 py-2 text-white text-sm">Add user</button>
        </form>
        <p class="mt-2 text-xs text-slate-500">New users are sent an email to set their password (requires mail to be configured). Existing users keep their current password.</p>
    </div>
</div>
@endsection

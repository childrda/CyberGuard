@extends('layouts.app')

@section('title', 'Add tenant')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Add tenant</h1>
    <p class="text-slate-400">Create a new organization (e.g. school or district) for campaigns and reports.</p>
</div>

<form method="post" action="{{ route('admin.tenants.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div>
        <label for="name" class="block text-sm font-medium text-slate-300">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="e.g. Lincoln County Schools">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="domain" class="block text-sm font-medium text-slate-300">Domain</label>
        <input type="text" name="domain" id="domain" value="{{ old('domain') }}" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="e.g. lcps.k12.va.us">
        <p class="mt-1 text-xs text-slate-500">Primary email domain for this tenant (used to match reports and allow sending).</p>
        @error('domain')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="slug" class="block text-sm font-medium text-slate-300">Slug (optional)</label>
        <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="e.g. lcps (leave blank to use domain)">
        <p class="mt-1 text-xs text-slate-500">Short identifier for URLs/API. Lowercase letters, numbers, hyphens only.</p>
        @error('slug')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="remediation_policy" class="block text-sm font-medium text-slate-300">Remediation policy</label>
        <select name="remediation_policy" id="remediation_policy"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            <option value="report_only" @selected(old('remediation_policy', 'analyst_approval_required') === 'report_only')>Report only (no mailbox removal)</option>
            <option value="analyst_approval_required" @selected(old('remediation_policy', 'analyst_approval_required') === 'analyst_approval_required')>Analyst approval required</option>
            <option value="auto_remove_confirmed_phish" @selected(old('remediation_policy') === 'auto_remove_confirmed_phish')>Auto-remove confirmed phish</option>
        </select>
        @error('remediation_policy')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="flex gap-2">
        <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Create tenant</button>
        <a href="{{ route('admin.settings.index') }}" class="rounded bg-slate-700 hover:bg-slate-600 px-4 py-2 text-slate-300">Cancel</a>
    </div>
</form>
@endsection

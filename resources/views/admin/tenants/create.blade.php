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
    <div class="rounded border border-slate-600 bg-slate-800/50 p-4">
        <h3 class="text-sm font-medium text-slate-300 mb-2">Webhook secret</h3>
        <label for="webhook_secret" class="block text-sm font-medium text-slate-300">Webhook secret (optional)</label>
        <input type="text" name="webhook_secret" id="webhook_secret" value="{{ old('webhook_secret') }}"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="Leave blank to auto-generate">
        <p class="mt-1 text-xs text-slate-500">Used to verify signatures from the Gmail Report Phish add-on.</p>
        @error('webhook_secret')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        <div class="mt-3 flex items-center gap-2">
            <input type="hidden" name="generate_webhook_secret" value="0">
            <input type="checkbox" name="generate_webhook_secret" id="generate_webhook_secret" value="1" {{ old('generate_webhook_secret', '1') ? 'checked' : '' }}
                class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
            <label for="generate_webhook_secret" class="text-sm text-slate-300">Generate a strong secret automatically</label>
        </div>
    </div>
    <div class="rounded border border-slate-600 bg-slate-800/50 p-4">
        <h3 class="text-sm font-medium text-slate-300 mb-2">Slack alerts (optional)</h3>
        <div class="flex items-center gap-2 mb-3">
            <input type="hidden" name="slack_alerts_enabled" value="0">
            <input type="checkbox" name="slack_alerts_enabled" id="slack_alerts_enabled" value="1" {{ old('slack_alerts_enabled') ? 'checked' : '' }}
                class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
            <label for="slack_alerts_enabled" class="text-sm text-slate-300">Enable Slack alerts for reported phishing/spam</label>
        </div>
        <label for="slack_bot_token" class="block text-sm font-medium text-slate-300">Slack bot token</label>
        <input type="text" name="slack_bot_token" id="slack_bot_token" value="{{ old('slack_bot_token') }}"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="xoxb-...">
        <p class="mt-1 text-xs text-slate-500">Token should have permissions to post and update messages in your target channel.</p>
        @error('slack_bot_token')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        <label for="slack_channel" class="block text-sm font-medium text-slate-300 mt-3">Slack channel</label>
        <input type="text" name="slack_channel" id="slack_channel" value="{{ old('slack_channel', 'phishing-alert') }}"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="phishing-alert">
        <p class="mt-1 text-xs text-slate-500">Channel name (without #) or channel ID.</p>
        @error('slack_channel')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
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

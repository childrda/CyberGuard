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
        <form method="post" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-4" enctype="multipart/form-data">
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
                <label for="allowed_domains" class="block text-sm font-medium text-slate-300">Allowed domains</label>
                <input type="text" name="allowed_domains" id="allowed_domains" value="{{ old('allowed_domains', is_array($tenant->allowed_domains) ? implode(', ', $tenant->allowed_domains) : '') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="e.g. lcps.k12.va.us, school.edu">
                <p class="mt-1 text-xs text-slate-500">Comma-separated. Only these domains may receive simulation emails. Required for launching campaigns.</p>
                @error('allowed_domains')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="rounded border border-slate-600 bg-slate-800/50 p-4">
                <h3 class="text-sm font-medium text-slate-300 mb-2">Report Phish webhook</h3>
                <label for="webhook_secret" class="block text-sm font-medium text-slate-300">Webhook secret</label>
                <input type="text" name="webhook_secret" id="webhook_secret" value="{{ old('webhook_secret') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="Leave blank to keep current secret">
                <p class="mt-1 text-xs text-slate-500">Current value is hidden for safety. Enter a new value to replace, or use rotate below.</p>
                @error('webhook_secret')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                <div class="mt-3 flex items-center gap-2">
                    <input type="hidden" name="generate_webhook_secret" value="0">
                    <input type="checkbox" name="generate_webhook_secret" id="generate_webhook_secret" value="1" {{ old('generate_webhook_secret') ? 'checked' : '' }}
                        class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                    <label for="generate_webhook_secret" class="text-sm text-slate-300">Rotate and auto-generate a new secret</label>
                </div>
            </div>
            <div class="rounded border border-slate-600 bg-slate-800/50 p-4">
                <h3 class="text-sm font-medium text-slate-300 mb-2">Slack alerts</h3>
                <div class="flex items-center gap-2 mb-3">
                    <input type="hidden" name="slack_alerts_enabled" value="0">
                    <input type="checkbox" name="slack_alerts_enabled" id="slack_alerts_enabled" value="1" {{ old('slack_alerts_enabled', $tenant->slack_alerts_enabled) ? 'checked' : '' }}
                        class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                    <label for="slack_alerts_enabled" class="text-sm text-slate-300">Enable Slack alerts for report workflow</label>
                </div>
                <label for="slack_bot_token" class="block text-sm font-medium text-slate-300">Slack bot token</label>
                <input type="text" name="slack_bot_token" id="slack_bot_token" value="{{ old('slack_bot_token') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="Leave blank to keep current token">
                <p class="mt-1 text-xs text-slate-500">Current value is hidden for safety. Enter a new token to replace it.</p>
                @error('slack_bot_token')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                <label for="slack_channel" class="block text-sm font-medium text-slate-300 mt-3">Slack channel</label>
                <input type="text" name="slack_channel" id="slack_channel" value="{{ old('slack_channel', $tenant->slack_channel ?: 'phishing-alert') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
                    placeholder="phishing-alert">
                <p class="mt-1 text-xs text-slate-500">Channel name (without #) or channel ID.</p>
                <p class="mt-2 text-xs text-amber-200/90">Slack jobs go to the <code class="rounded bg-slate-900 px-1">{{ config('phishing.slack_queue') }}</code> queue (<code class="rounded bg-slate-900 px-1">PHISHING_SLACK_QUEUE</code> in <code class="rounded bg-slate-900 px-1">.env</code>). Your <code class="rounded bg-slate-900 px-1">queue:work --queue=...</code> list must include that name (e.g. <code class="rounded bg-slate-900 px-1">notifications</code>).</p>
                @error('slack_channel')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
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
            <div class="border-t border-slate-600 pt-4 mt-4">
                <h3 class="text-sm font-medium text-slate-300 mb-3">Directory integration (Google Workspace)</h3>
                <p class="text-xs text-slate-500 mb-3">Connect to search groups and OUs when creating campaigns. Service account JSON with domain-wide delegation (Admin SDK Directory API). When creating a campaign, choose target type <strong>Group</strong> or <strong>OU</strong> to search and select user groups from your workspace.</p>
                <div class="space-y-3">
                    <div>
                        <label for="google_credentials_file" class="block text-xs font-medium text-slate-400">Upload service account JSON</label>
                        <input type="file" name="google_credentials_file" id="google_credentials_file" accept=".json,application/json"
                            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm file:mr-2 file:rounded file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:text-sm file:text-white file:hover:bg-blue-500">
                        <p class="mt-1 text-xs text-slate-500">File is stored securely on the server for this tenant only; not accessible via the web. Upload a new file to replace the existing one.</p>
                        @error('google_credentials_file')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
                        @if($tenant->google_credentials_path && \Illuminate\Support\Str::contains($tenant->google_credentials_path, 'tenant-credentials'))
                            <p class="mt-1 text-xs text-green-400">Credentials are stored for this tenant.</p>
                        @endif
                    </div>
                    <div>
                        <label for="google_credentials_path" class="block text-xs font-medium text-slate-400">Or enter server path (optional)</label>
                        <input type="text" name="google_credentials_path" id="google_credentials_path" value="{{ old('google_credentials_path', $tenant->google_credentials_path) }}"
                            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm placeholder-slate-500"
                            placeholder="e.g. /secure/tenant-keys/google-service-account.json">
                        <p class="mt-1 text-xs text-slate-500">Only if your server has the JSON at a fixed path; leave blank to use the uploaded file above.</p>
                    </div>
                    <div>
                        <label for="google_admin_user" class="block text-xs font-medium text-slate-400">Admin email (impersonate for API)</label>
                        <input type="email" name="google_admin_user" id="google_admin_user" value="{{ old('google_admin_user', $tenant->google_admin_user) }}"
                            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm placeholder-slate-500"
                            placeholder="admin@{{ $tenant->domain }}">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="directory_sync_enabled" value="0">
                        <input type="checkbox" name="directory_sync_enabled" id="directory_sync_enabled" value="1" {{ old('directory_sync_enabled', $tenant->directory_sync_enabled) ? 'checked' : '' }}
                            class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                        <label for="directory_sync_enabled" class="text-sm text-slate-300">Enable directory sync</label>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-600 pt-4 mt-4">
                <h3 class="text-sm font-medium text-slate-300 mb-2">Gamification</h3>
                <p class="text-xs text-slate-500 mb-2">When enabled, users get feedback (points, leaderboard, badges) for reporting and training. When disabled, campaigns still send and track clicks/submits but no points or leaderboard.</p>
                @if(auth()->user()?->isPlatformAdmin())
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="gamification_enabled" value="0">
                        <input type="checkbox" name="gamification_enabled" id="gamification_enabled" value="1" {{ old('gamification_enabled', $tenant->gamification_enabled) ? 'checked' : '' }}
                            class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                        <label for="gamification_enabled" class="text-sm text-slate-300">Enable gamification</label>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Gamification is <strong>{{ $tenant->gamification_enabled ? 'enabled' : 'disabled' }}</strong>. Only the platform administrator can change this.</p>
                @endif
            </div>
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

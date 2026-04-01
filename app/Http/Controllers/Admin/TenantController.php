<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingAttack;
use App\Models\PhishingTemplate;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PhishingAttackSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function create(): View
    {
        if (! auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Only platform administrators can add tenants.');
        }
        return view('admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Only platform administrators can add tenants.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug', 'regex:/^[a-z0-9\-]+$/'],
            'remediation_policy' => ['required', 'in:report_only,analyst_approval_required,auto_remove_confirmed_phish'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'generate_webhook_secret' => ['nullable', 'boolean'],
            'slack_alerts_enabled' => ['nullable', 'boolean'],
            'slack_bot_token' => ['nullable', 'string', 'max:255'],
            'slack_channel' => ['nullable', 'string', 'max:120'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['domain']);
        if (Tenant::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(4);
        }

        $webhookSecret = trim((string) ($validated['webhook_secret'] ?? ''));
        if ((bool) ($validated['generate_webhook_secret'] ?? false) || $webhookSecret === '') {
            $webhookSecret = Str::random(64);
        }

        $slackEnabled = (bool) ($validated['slack_alerts_enabled'] ?? false);
        $slackToken = trim((string) ($validated['slack_bot_token'] ?? '')) ?: null;
        $slackChannel = trim((string) ($validated['slack_channel'] ?? '')) ?: 'phishing-alert';
        if ($slackEnabled && ! $slackToken) {
            return redirect()->back()->withInput()->withErrors([
                'slack_bot_token' => 'Slack bot token is required when Slack alerts are enabled.',
            ]);
        }

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => strtolower($validated['domain']),
            'slug' => $slug,
            'remediation_policy' => $validated['remediation_policy'],
            'webhook_secret' => $webhookSecret,
            'slack_alerts_enabled' => $slackEnabled,
            'slack_bot_token' => $slackToken,
            'slack_channel' => $slackChannel,
            'active' => true,
        ]);

        // Copy attack templates so the tenant has something to start with
        $sourceAttack = PhishingAttack::withoutGlobalScope('tenant')->whereNotNull('tenant_id')->first();
        if ($sourceAttack) {
            $sourceAttacks = PhishingAttack::withoutGlobalScope('tenant')->where('tenant_id', $sourceAttack->tenant_id)->get();
            foreach ($sourceAttacks as $a) {
                PhishingAttack::withoutGlobalScope('tenant')->create([
                    'tenant_id' => $tenant->id,
                    'name' => $a->name,
                    'description' => $a->description,
                    'category' => $a->category,
                    'subject' => $a->subject,
                    'from_name' => $a->from_name,
                    'from_email' => $a->from_email,
                    'reply_to' => $a->reply_to,
                    'html_body' => $a->html_body,
                    'text_body' => $a->text_body,
                    'difficulty_rating' => $a->difficulty_rating,
                    'times_sent' => 0,
                    'times_clicked' => 0,
                    'landing_page_type' => $a->landing_page_type,
                    'training_page_id' => null,
                    'tags' => $a->tags,
                    'active' => $a->active,
                ]);
            }
        } else {
            (new PhishingAttackSeeder)->seedAttacksForTenant($tenant);
        }

        // Copy phishing templates so the tenant has something to start with
        $sourceTemplate = PhishingTemplate::withoutGlobalScope('tenant')->whereNotNull('tenant_id')->first();
        if ($sourceTemplate) {
            $sourceTemplates = PhishingTemplate::withoutGlobalScope('tenant')->where('tenant_id', $sourceTemplate->tenant_id)->get();
            foreach ($sourceTemplates as $t) {
                PhishingTemplate::withoutGlobalScope('tenant')->create([
                    'tenant_id' => $tenant->id,
                    'name' => $t->name,
                    'subject' => $t->subject,
                    'html_body' => $t->html_body,
                    'text_body' => $t->text_body,
                    'sender_name' => $t->sender_name,
                    'sender_email' => $t->sender_email,
                    'reply_to' => $t->reply_to,
                    'landing_page_type' => $t->landing_page_type,
                    'training_page_id' => null,
                    'difficulty' => $t->difficulty,
                    'tags' => $t->tags,
                    'active' => $t->active,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('admin.settings.index')->with('success', 'Tenant created with default attack templates and templates. You can switch to it in the sidebar.');
    }

    public function edit(Tenant $tenant): View
    {
        $user = auth()->user();
        $canEdit = $user?->isPlatformAdmin() || $user?->tenant_id === $tenant->id;
        if (! $canEdit) {
            abort(403, 'You do not have permission to edit this tenant.');
        }
        $tenant->load([]);
        $users = User::where('tenant_id', $tenant->id)->with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        return view('admin.tenants.edit', compact('tenant', 'users', 'roles'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = auth()->user();
        $canEdit = $user?->isPlatformAdmin() || $user?->tenant_id === $tenant->id;
        if (! $canEdit) {
            abort(403, 'You do not have permission to edit this tenant.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain,'.$tenant->id],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:tenants,slug,'.$tenant->id],
            'allowed_domains' => ['nullable', 'string', 'max:2000'],
            'remediation_policy' => ['required', 'in:report_only,analyst_approval_required,auto_remove_confirmed_phish'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'generate_webhook_secret' => ['nullable', 'boolean'],
            'slack_alerts_enabled' => ['nullable', 'boolean'],
            'slack_bot_token' => ['nullable', 'string', 'max:255'],
            'slack_channel' => ['nullable', 'string', 'max:120'],
            'google_credentials_file' => ['nullable', 'file', 'mimes:json', 'max:102400'],
            'google_credentials_path' => ['nullable', 'string', 'max:500'],
            'google_admin_user' => ['nullable', 'email', 'max:255'],
            'directory_sync_enabled' => ['nullable', 'boolean'],
            'gamification_enabled' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        $allowedDomains = $validated['allowed_domains'] ?? '';
        $allowedList = array_values(array_filter(array_map('trim', explode(',', $allowedDomains))));

        $credentialsPath = $tenant->google_credentials_path;
        if ($request->hasFile('google_credentials_file')) {
            $file = $request->file('google_credentials_file');
            $contents = $file->get();
            $json = @json_decode($contents, true);
            if (! is_array($json) || empty($json['type']) || empty($json['private_key']) || empty($json['client_email'])) {
                return redirect()->back()
                    ->withInput($request->except('google_credentials_file'))
                    ->withErrors(['google_credentials_file' => 'File must be a valid Google service account JSON (type, client_email, and private_key).']);
            }
            $dir = 'tenant-credentials/'.$tenant->id;
            Storage::disk('local')->makeDirectory($dir);
            Storage::disk('local')->put($dir.'/google-credentials.json', $contents);
            $credentialsPath = storage_path('app/'.$dir.'/google-credentials.json');
        } elseif (trim((string) ($validated['google_credentials_path'] ?? '')) !== '') {
            $credentialsPath = trim($validated['google_credentials_path']);
        }

        $webhookSecret = $tenant->webhook_secret;
        $newWebhookSecret = trim((string) ($validated['webhook_secret'] ?? ''));
        if ((bool) ($validated['generate_webhook_secret'] ?? false)) {
            $webhookSecret = Str::random(64);
        } elseif ($newWebhookSecret !== '') {
            $webhookSecret = $newWebhookSecret;
        }

        $slackToken = trim((string) ($validated['slack_bot_token'] ?? ''));
        if ($slackToken === '') {
            $slackToken = $tenant->slack_bot_token;
        }

        $slackChannel = trim((string) ($validated['slack_channel'] ?? ''));
        if ($slackChannel === '') {
            $slackChannel = $tenant->slack_channel ?: 'phishing-alert';
        }
        $slackEnabled = (bool) ($validated['slack_alerts_enabled'] ?? false);
        if ($slackEnabled && ! $slackToken) {
            return redirect()->back()->withInput($request->except('slack_bot_token'))->withErrors([
                'slack_bot_token' => 'Slack bot token is required when Slack alerts are enabled.',
            ]);
        }

        $tenant->update([
            'name' => $validated['name'],
            'domain' => strtolower($validated['domain']),
            'slug' => $validated['slug'],
            'allowed_domains' => $allowedList,
            'remediation_policy' => $validated['remediation_policy'],
            'webhook_secret' => $webhookSecret,
            'slack_alerts_enabled' => $slackEnabled,
            'slack_bot_token' => $slackToken,
            'slack_channel' => $slackChannel,
            'google_credentials_path' => $credentialsPath,
            'google_admin_user' => trim((string) ($validated['google_admin_user'] ?? '')) !== '' ? trim($validated['google_admin_user']) : $tenant->google_admin_user,
            'directory_sync_enabled' => (bool) ($validated['directory_sync_enabled'] ?? false),
            'gamification_enabled' => auth()->user()?->isPlatformAdmin()
                ? (bool) ($validated['gamification_enabled'] ?? false)
                : $tenant->gamification_enabled,
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.tenants.edit', $tenant)->with('success', 'Tenant updated.');
    }

    /**
     * Add a user to this tenant (platform admin only). Creates the user if they don't exist.
     */
    public function addUser(Request $request, Tenant $tenant): RedirectResponse
    {
        if (! auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Only platform administrators can add users to tenants.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'user_name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:superadmin,campaign_admin,analyst,viewer'],
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $user->update(['tenant_id' => $tenant->id]);
            $user->roles()->sync([$role->id]);
            $message = "User {$user->email} assigned to this tenant with role {$role->name}.";
        } else {
            $name = ! empty($validated['user_name']) ? $validated['user_name'] : Str::before($validated['email'], '@');
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)),
            ]);
            $user->roles()->attach($role->id);
            $resetSent = false;
            try {
                $resetSent = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT;
                if (! $resetSent) {
                    SystemLog::log('mail_failed', 'Set-password email not sent for new user.', ['user_id' => $user->id, 'email' => $user->email]);
                }
            } catch (\Throwable $e) {
                SystemLog::log('mail_failed', 'Set-password email failed: '.$e->getMessage(), ['user_id' => $user->id, 'email' => $user->email]);
            }
            $message = $resetSent
                ? "User {$user->email} created with role {$role->name}. A link to set their password has been sent to their email."
                : "User {$user->email} created with role {$role->name}. They can sign in at the login page and use “Forgot password?” to set a password (see System log if mail failed).";
        }

        return redirect()->route('admin.tenants.edit', $tenant)->with('success', $message);
    }
}

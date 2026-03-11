<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingAttack;
use App\Models\Role;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PhishingAttackSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['domain']);
        if (Tenant::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(4);
        }

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'domain' => strtolower($validated['domain']),
            'slug' => $slug,
            'remediation_policy' => $validated['remediation_policy'],
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
                    'subject' => $a->subject,
                    'from_name' => $a->from_name,
                    'from_email' => $a->from_email,
                    'html_body' => $a->html_body,
                    'text_body' => $a->text_body,
                    'difficulty_rating' => $a->difficulty_rating,
                    'times_sent' => 0,
                    'times_clicked' => 0,
                    'landing_page_type' => $a->landing_page_type,
                    'training_page_id' => null,
                    'active' => $a->active,
                ]);
            }
        } else {
            (new PhishingAttackSeeder)->seedAttacksForTenant($tenant);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Tenant created with default attack templates. You can switch to it in the sidebar.');
    }

    public function edit(Tenant $tenant): View
    {
        if (! auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Only platform administrators can edit tenants.');
        }
        $tenant->load([]);
        $users = User::where('tenant_id', $tenant->id)->with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        return view('admin.tenants.edit', compact('tenant', 'users', 'roles'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        if (! auth()->user()?->isPlatformAdmin()) {
            abort(403, 'Only platform administrators can edit tenants.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain,'.$tenant->id],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:tenants,slug,'.$tenant->id],
            'remediation_policy' => ['required', 'in:report_only,analyst_approval_required,auto_remove_confirmed_phish'],
            'active' => ['nullable', 'boolean'],
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'domain' => strtolower($validated['domain']),
            'slug' => $validated['slug'],
            'remediation_policy' => $validated['remediation_policy'],
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

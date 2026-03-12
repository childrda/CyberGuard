<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CyberguardInstaller
{
    /**
     * Whether the installer can run (no tenant and no superadmin yet), unless forced.
     */
    public function canRun(bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        if (Tenant::exists()) {
            return false;
        }

        $hasSuperadmin = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))
            ->exists();

        return ! $hasSuperadmin;
    }

    /**
     * Ensure roles exist (idempotent).
     */
    public function ensureRoles(): void
    {
        $roles = [
            ['name' => 'superadmin', 'label' => 'Super Admin', 'description' => 'Full access'],
            ['name' => 'campaign_admin', 'label' => 'Campaign Admin', 'description' => 'Create and launch campaigns'],
            ['name' => 'analyst', 'label' => 'Analyst', 'description' => 'View reports and audit'],
            ['name' => 'viewer', 'label' => 'Viewer', 'description' => 'Read-only access'],
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r['name']], $r);
        }
    }

    /**
     * Create tenant and platform superadmin user. Roles must exist (call ensureRoles first).
     *
     * @param  array{tenant_name: string, tenant_domain: string, tenant_slug: string, allowed_domains: array<int, string>, admin_name: string, admin_email: string, admin_password: string}  $input
     * @return array{tenant: Tenant, user: User}
     */
    public function install(array $input): array
    {
        $this->validateInput($input);

        return DB::transaction(function () use ($input) {
            $this->ensureRoles();

            $tenant = Tenant::create([
                'name' => $input['tenant_name'],
                'domain' => $input['tenant_domain'],
                'slug' => $input['tenant_slug'],
                'allowed_domains' => $input['allowed_domains'],
                'remediation_policy' => 'analyst_approval_required',
                'active' => true,
            ]);

            $user = User::create([
                'name' => $input['admin_name'],
                'email' => $input['admin_email'],
                'password' => Hash::make($input['admin_password']),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ]);

            $superadmin = Role::where('name', 'superadmin')->firstOrFail();
            $user->roles()->attach($superadmin->id);

            return ['tenant' => $tenant, 'user' => $user];
        });
    }

    /**
     * @param  array{tenant_name: string, tenant_domain: string, tenant_slug: string, allowed_domains: array<int, string>, admin_name: string, admin_email: string, admin_password: string}  $input
     */
    protected function validateInput(array $input): void
    {
        $messages = [];

        if (empty(trim((string) ($input['tenant_name'] ?? '')))) {
            $messages[] = 'Tenant name is required.';
        }
        if (empty(trim((string) ($input['tenant_domain'] ?? '')))) {
            $messages[] = 'Tenant domain is required.';
        }
        if (Tenant::where('domain', $input['tenant_domain'])->exists()) {
            $messages[] = 'A tenant with this domain already exists.';
        }
        if (empty(trim((string) ($input['tenant_slug'] ?? '')))) {
            $messages[] = 'Tenant slug is required.';
        }
        if (Tenant::where('slug', $input['tenant_slug'])->exists()) {
            $messages[] = 'A tenant with this slug already exists.';
        }
        $allowed = $input['allowed_domains'] ?? [];
        if (! is_array($allowed) || count(array_filter($allowed)) === 0) {
            $messages[] = 'At least one allowed domain is required.';
        }
        if (empty(trim((string) ($input['admin_name'] ?? '')))) {
            $messages[] = 'Admin name is required.';
        }
        if (empty(trim((string) ($input['admin_email'] ?? '')))) {
            $messages[] = 'Admin email is required.';
        }
        if (! filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $messages[] = 'Admin email must be a valid email address.';
        }
        if (User::where('email', $input['admin_email'])->exists()) {
            $messages[] = 'A user with this email already exists.';
        }
        $pw = $input['admin_password'] ?? '';
        if (strlen($pw) < 8) {
            $messages[] = 'Password must be at least 8 characters.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages(['input' => $messages]);
        }
    }

    /**
     * Generate a unique slug from name or domain.
     */
    public function suggestSlug(string $name, string $domain): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = Str::slug($domain);
        }
        if ($slug === '') {
            $slug = 'tenant';
        }
        $base = $slug;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * Parse allowed domains string (comma-separated) into array of trimmed, non-empty values.
     *
     * @return array<int, string>
     */
    public function parseAllowedDomains(string $value): array
    {
        $domains = array_map('trim', explode(',', $value));

        return array_values(array_filter($domains, fn ($d) => $d !== ''));
    }
}

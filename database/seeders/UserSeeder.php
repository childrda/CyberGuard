<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = 'password';
        if (app()->environment('production')) {
            $defaultPassword = env('SEEDER_DEFAULT_PASSWORD');
            if (empty($defaultPassword) || $defaultPassword === 'password') {
                $this->command->warn('UserSeeder: In production, set SEEDER_DEFAULT_PASSWORD in .env to a strong password, or skip this seeder.');
                return;
            }
        }

        $defaultTenant = Tenant::where('domain', 'example.com')->first();
        $tenantId = $defaultTenant?->id;

        // Platform superadmin: no tenant_id → can switch to any tenant
        $platformAdmin = User::firstOrCreate(
            ['email' => 'platform_admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
                'tenant_id' => null,
            ]
        );
        $platformAdmin->roles()->syncWithoutDetaching(Role::where('name', 'superadmin')->pluck('id'));

        // Tenant-scoped admin: belongs to default tenant only
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
                'tenant_id' => $tenantId,
            ]
        );
        if ($admin->wasRecentlyCreated === false && $admin->tenant_id === null && $tenantId) {
            $admin->update(['tenant_id' => $tenantId]);
        }
        $admin->roles()->syncWithoutDetaching(Role::where('name', 'superadmin')->pluck('id'));

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
                'tenant_id' => $tenantId,
            ]
        );
        if ($viewer->wasRecentlyCreated === false && $viewer->tenant_id === null && $tenantId) {
            $viewer->update(['tenant_id' => $tenantId]);
        }
        $viewer->roles()->syncWithoutDetaching(Role::where('name', 'viewer')->pluck('id'));
    }
}

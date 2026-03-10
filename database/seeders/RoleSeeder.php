<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
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
}

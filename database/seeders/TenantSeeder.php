<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['domain' => 'example.com'],
            [
                'name' => 'Default',
                'slug' => 'default',
                'remediation_policy' => 'analyst_approval_required',
                'active' => true,
            ]
        );
    }
}

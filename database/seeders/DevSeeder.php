<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DevSeeder extends Seeder
{
    /**
     * Seed demo tenant and users (example.com, admin@example.com, etc.).
     * For local development only. Do not run in production.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(
                'DevSeeder may only be run in local environment. Use cyberguard:install for production setup.'
            );
        }

        $this->call([
            TenantSeeder::class,
            UserSeeder::class,
            PhishingTemplateSeeder::class,
            PhishingCampaignSeeder::class,
            PhishingAttackSeeder::class,
            BadgeSeeder::class,
        ]);
    }
}

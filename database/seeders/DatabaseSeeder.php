<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application database. Intended for local/dev only.
     * In production, seeding is blocked unless SEEDER_ALLOW_PRODUCTION=true in .env.
     */
    public function run(): void
    {
        if (app()->environment('production') && strtolower((string) env('SEEDER_ALLOW_PRODUCTION', '')) !== 'true') {
            throw new RuntimeException(
                'Database seeding is disabled in production. Set SEEDER_ALLOW_PRODUCTION=true in .env only if you intentionally want to run seeders (e.g. initial bootstrap). Default credentials must never be used in production.'
            );
        }

        $this->call([
            TenantSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            LandingPageSeeder::class,
            PhishingTemplateSeeder::class,
            PhishingCampaignSeeder::class,
            PhishingAttackSeeder::class,
        ]);
    }
}

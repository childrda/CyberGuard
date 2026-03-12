<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application database.
     *
     * Does NOT create any tenant or admin users. Use `php artisan cyberguard:install`
     * for first-run setup. For local/demo data (default tenant + users), run
     * `php artisan db:seed --class=DevSeeder` (local only).
     */
    public function run(): void
    {
        if (app()->environment('production') && strtolower((string) env('SEEDER_ALLOW_PRODUCTION', '')) !== 'true') {
            throw new RuntimeException(
                'Database seeding is disabled in production. Set SEEDER_ALLOW_PRODUCTION=true only if you intend to run seeders (e.g. roles and landing pages after cyberguard:install).'
            );
        }

        $this->call([
            RoleSeeder::class,
            LandingPageSeeder::class,
        ]);
    }
}

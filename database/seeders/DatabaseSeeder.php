<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            LandingPageSeeder::class,
            PhishingTemplateSeeder::class,
            PhishingCampaignSeeder::class,
        ]);
    }
}

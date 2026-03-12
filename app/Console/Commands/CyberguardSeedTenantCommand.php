<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\PhishingAttackSeeder;
use Database\Seeders\PhishingTemplateSeeder;
use Illuminate\Console\Command;

class CyberguardSeedTenantCommand extends Command
{
    protected $signature = 'cyberguard:seed-tenant
                            {--slug= : Tenant slug (e.g. louisa-county-schools)}
                            {--domain= : Tenant domain (e.g. lcps.k12.va.us)}';

    protected $description = 'Seed default template, attacks, and badges for an existing tenant (e.g. after a partial install)';

    public function handle(): int
    {
        $slug = $this->option('slug');
        $domain = $this->option('domain');

        if (! $slug && ! $domain) {
            $slug = $this->ask('Tenant slug or domain', '');
            if ($slug === '') {
                $this->error('Provide --slug= or --domain= or enter a value when prompted.');

                return self::FAILURE;
            }
            if (str_contains($slug, '.')) {
                $domain = $slug;
                $slug = null;
            }
        }

        $tenant = $slug
            ? Tenant::where('slug', $slug)->first()
            : Tenant::where('domain', $domain)->first();

        if (! $tenant) {
            $this->error('Tenant not found. Use slug (e.g. louisa-county-schools) or domain (e.g. lcps.k12.va.us).');

            return self::FAILURE;
        }

        $user = User::whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))->first();

        $this->info("Seeding tenant: {$tenant->name} ({$tenant->slug})");

        PhishingTemplateSeeder::seedDefaultTemplateForTenant($tenant, $user);
        $this->info('Sample email template seeded.');

        PhishingAttackSeeder::seedForTenant($tenant);
        $this->info('Default phishing attacks seeded.');

        $badgeSeeder = new BadgeSeeder;
        $badgeSeeder->setCommand($this);
        $badgeSeeder->run();
        $this->info('Default badges seeded.');

        $this->info('Done.');

        return self::SUCCESS;
    }
}

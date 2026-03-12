<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\CyberguardInstaller;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\LandingPageSeeder;
use Database\Seeders\PhishingAttackSeeder;
use Database\Seeders\PhishingTemplateSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CyberguardInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cyberguard:install
                            {--force : Run even if a tenant or superadmin already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive first-run installer: create initial tenant and superadmin (no default credentials)';

    public function handle(CyberguardInstaller $installer): int
    {
        if (! Schema::hasTable('tenants')) {
            $this->error('Database not migrated. Run: php artisan migrate');

            return self::FAILURE;
        }

        if (! $installer->canRun($this->option('force'))) {
            $this->error('Installation skipped: at least one tenant or superadmin already exists. Use --force to run anyway (not recommended in production).');

            return self::FAILURE;
        }

        $this->info('CyberGuard first-run installer');
        $this->info('You will create the first tenant and a platform superadmin user.');
        $this->newLine();

        $tenantName = $this->ask('Tenant name', '');
        $tenantDomain = $this->ask('Tenant domain (e.g. company.com)', '');
        $allowedDomainsRaw = $this->ask('Allowed domains (comma-separated, for simulation targets)', $tenantDomain);
        $allowedDomains = $installer->parseAllowedDomains($allowedDomainsRaw);
        if (count($allowedDomains) === 0 && $tenantDomain !== '') {
            $allowedDomains = [$tenantDomain];
        }
        $suggestedSlug = $installer->suggestSlug($tenantName, $tenantDomain);
        $tenantSlug = $this->ask('Tenant slug (leave empty to use: '.$suggestedSlug.')', '');
        if (trim($tenantSlug) === '') {
            $tenantSlug = $suggestedSlug;
        } else {
            $tenantSlug = Str::slug($tenantSlug);
            if ($tenantSlug === '') {
                $tenantSlug = $suggestedSlug;
            }
        }
        $adminName = $this->ask('Super admin name', '');
        $adminEmail = $this->ask('Super admin email', '');
        $adminPassword = $this->secret('Super admin password (min 8 characters)');
        $adminPasswordConfirm = $this->secret('Confirm super admin password');
        if ($adminPassword !== $adminPasswordConfirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $input = [
            'tenant_name' => trim($tenantName),
            'tenant_domain' => trim(strtolower($tenantDomain)),
            'tenant_slug' => $tenantSlug,
            'allowed_domains' => $allowedDomains,
            'admin_name' => trim($adminName),
            'admin_email' => trim(strtolower($adminEmail)),
            'admin_password' => $adminPassword,
        ];

        try {
            $result = $installer->install($input);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $msgs) {
                foreach ((array) $msgs as $msg) {
                    $this->error($msg);
                }
            }

            return self::FAILURE;
        }

        $tenant = $result['tenant'];
        $user = $result['user'];

        $this->newLine();
        $this->info('Tenant and superadmin created successfully.');
        $this->table(
            ['Item', 'Value'],
            [
                ['Tenant', $tenant->name.' ('.$tenant->domain.')'],
                ['Tenant slug', $tenant->slug],
                ['Super admin', $user->email],
            ]
        );

        $this->ensureLandingPage();
        PhishingTemplateSeeder::seedDefaultTemplateForTenant($tenant, $user);
        $this->info('Sample email template seeded for tenant.');
        $this->seedDefaultAttacksForTenant($tenant);
        $this->seedBadgesForTenant($tenant);

        $this->info('You can log in at your app URL and select the tenant from the dropdown.');

        return self::SUCCESS;
    }

    protected function ensureLandingPage(): void
    {
        $seeder = new LandingPageSeeder;
        $seeder->setCommand($this);
        $seeder->run();
    }

    protected function seedDefaultAttacksForTenant(Tenant $tenant): void
    {
        PhishingAttackSeeder::seedForTenant($tenant);
        $this->info('Default phishing attacks seeded for tenant.');
    }

    protected function seedBadgesForTenant(Tenant $tenant): void
    {
        $seeder = new BadgeSeeder;
        $seeder->setCommand($this);
        $seeder->run();
        $this->info('Default badges seeded for tenant.');
    }
}

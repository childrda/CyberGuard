<?php

namespace App\Services;

use App\Models\Tenant;

class RemediationPreflightService
{
    /**
     * Validate tenant remediation prerequisites before queueing/running a job.
     *
     * @return array{ok: bool, error?: string}
     */
    public function checkTenant(Tenant $tenant): array
    {
        if (! config('phishing.gmail_removal_enabled')) {
            return ['ok' => false, 'error' => 'Gmail removal is disabled. Set PHISHING_GMAIL_REMOVAL_ENABLED=true.'];
        }

        $path = trim((string) ($tenant->google_credentials_path ?? ''));
        if ($path === '') {
            return ['ok' => false, 'error' => 'Tenant Google credentials path is not set. Upload credentials in tenant settings.'];
        }
        if (! is_file($path) || ! is_readable($path)) {
            return ['ok' => false, 'error' => 'Tenant Google credentials file is missing or not readable by the app/worker user.'];
        }

        $adminUser = trim((string) ($tenant->google_admin_user ?? ''));
        if ($adminUser === '' || ! filter_var($adminUser, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Tenant Google admin user is not set or invalid.'];
        }

        return ['ok' => true];
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'slug',
        'allowed_domains',
        'google_credentials_path',
        'webhook_secret',
        'addon_config',
        'campaign_settings',
        'reporting_rules',
        'remediation_policy',
        'google_admin_user',
        'directory_sync_enabled',
        'gamification_enabled',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'addon_config' => 'array',
            'campaign_settings' => 'array',
            'reporting_rules' => 'array',
            'allowed_domains' => 'array',
            'directory_sync_enabled' => 'boolean',
            'gamification_enabled' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * Allowed domains for this tenant (simulation emails only to these). Replaces .env PHISHING_ALLOWED_DOMAINS.
     */
    public function getAllowedDomainsList(): array
    {
        $list = $this->allowed_domains ?? [];
        if (is_array($list) && ! empty($list)) {
            return array_values(array_filter(array_map('strtolower', array_map('trim', $list))));
        }
        return [];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(PhishingCampaign::class, 'tenant_id');
    }

    public function reportedMessages(): HasMany
    {
        return $this->hasMany(ReportedMessage::class, 'tenant_id');
    }

    public function remediationJobs(): HasMany
    {
        return $this->hasMany(RemediationJob::class, 'tenant_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'tenant_id');
    }

    public function shieldPointsLedger(): HasMany
    {
        return $this->hasMany(ShieldPointsLedger::class, 'tenant_id');
    }

    public function scorePeriods(): HasMany
    {
        return $this->hasMany(ScorePeriod::class, 'tenant_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(Badge::class, 'tenant_id');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'tenant_id');
    }

    public function isReportOnly(): bool
    {
        return $this->remediation_policy === 'report_only';
    }

    public function requiresAnalystApproval(): bool
    {
        return $this->remediation_policy === 'analyst_approval_required';
    }

    public function isAutoRemove(): bool
    {
        return $this->remediation_policy === 'auto_remove_confirmed_phish';
    }

public static function currentId(): ?int
{
    return request()->attributes->get('current_tenant_id');
}

    public static function current(): ?self
    {
        $id = self::currentId();
        return $id ? self::find($id) : null;
    }
}

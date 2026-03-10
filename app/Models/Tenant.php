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
        'google_credentials_path',
        'webhook_secret',
        'addon_config',
        'campaign_settings',
        'reporting_rules',
        'remediation_policy',
        'google_admin_user',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'addon_config' => 'array',
            'campaign_settings' => 'array',
            'reporting_rules' => 'array',
            'active' => 'boolean',
        ];
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

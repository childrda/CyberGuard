<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScorePeriod extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shieldPointsLedgerEntries(): HasMany
    {
        return $this->hasMany(ShieldPointsLedger::class, 'score_period_id');
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'score_period_id');
    }

    /**
     * Whether the given date falls within this period (inclusive).
     */
    public function containsDate(\DateTimeInterface|string $date): bool
    {
        $d = $date instanceof \DateTimeInterface ? $date : new \DateTimeImmutable($date);
        return $d >= $this->start_date->startOfDay() && $d <= $this->end_date->endOfDay();
    }

    /**
     * Get the current score period for a tenant (where is_current = true).
     * Falls back to a period containing today if no current is set.
     */
    public static function currentForTenant(int $tenantId): ?self
    {
        $period = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->first();

        if ($period) {
            return $period;
        }

        return self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->orderByDesc('start_date')
            ->first();
    }
}

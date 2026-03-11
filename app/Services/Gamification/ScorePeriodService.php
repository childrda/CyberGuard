<?php

namespace App\Services\Gamification;

use App\Models\ScorePeriod;
use Carbon\Carbon;

/**
 * Create and manage score periods (terms/semesters) for tenant leaderboards and points.
 */
class ScorePeriodService
{
    /**
     * List score periods for a tenant, most recent first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ScorePeriod>
     */
    public function listForTenant(int $tenantId, int $limit = 20)
    {
        return ScorePeriod::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('end_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new score period. Does not set is_current; use setCurrent() after if needed.
     */
    public function create(
        int $tenantId,
        string $name,
        string $slug,
        \DateTimeInterface|string $startDate,
        \DateTimeInterface|string $endDate,
        bool $isCurrent = false
    ): ScorePeriod {
        $start = $startDate instanceof \DateTimeInterface ? $startDate : new Carbon($startDate);
        $end = $endDate instanceof \DateTimeInterface ? $endDate : new Carbon($endDate);

        if ($isCurrent) {
            $this->clearCurrentForTenant($tenantId);
        }

        return ScorePeriod::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'is_current' => $isCurrent,
        ]);
    }

    /**
     * Set the given period as the current one for the tenant (clears any other current).
     */
    public function setCurrent(int $tenantId, int $scorePeriodId): void
    {
        $this->clearCurrentForTenant($tenantId);
        ScorePeriod::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $scorePeriodId)
            ->update(['is_current' => true]);
    }

    /**
     * Clear is_current for all periods in the tenant.
     */
    public function clearCurrentForTenant(int $tenantId): void
    {
        ScorePeriod::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->update(['is_current' => false]);
    }
}

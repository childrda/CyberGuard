<?php

namespace App\Services\Gamification;

use App\Models\ScorePeriod;
use App\Models\ShieldPointsLedger;

/**
 * Awards points and writes to shield_points_ledger with optional score_period_id.
 * Resolves the tenant's current score period when none is provided.
 */
class PointsService
{
    /**
     * Award points to a user and write a ledger entry.
     * When $scorePeriodId is null, the tenant's current score period (if any) is attached.
     *
     * @param  int  $tenantId
     * @param  string  $userIdentifier  Email or identifier (matches ledger)
     * @param  string  $eventType  e.g. simulation_reported, reported_phish, training_completed, clicked, submitted
     * @param  int  $pointsDelta  Positive or negative points
     * @param  array{reason?: string|null, campaign_id?: int|null, reported_message_id?: int|null, user_id?: int|null, score_period_id?: int|null}  $options
     */
    public function award(
        int $tenantId,
        string $userIdentifier,
        string $eventType,
        int $pointsDelta,
        array $options = []
    ): ShieldPointsLedger {
        $scorePeriodId = $options['score_period_id'] ?? null;
        if ($scorePeriodId === null) {
            $period = ScorePeriod::currentForTenant($tenantId);
            $scorePeriodId = $period?->id;
        }

        return ShieldPointsLedger::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'user_identifier' => $userIdentifier,
            'user_id' => $options['user_id'] ?? null,
            'event_type' => $eventType,
            'points_delta' => $pointsDelta,
            'reason' => $options['reason'] ?? null,
            'campaign_id' => $options['campaign_id'] ?? null,
            'reported_message_id' => $options['reported_message_id'] ?? null,
            'score_period_id' => $scorePeriodId,
            'created_at' => now(),
        ]);
    }

    /**
     * Sum of points_delta for a user in a tenant, optionally scoped by score period or month.
     * When $scorePeriodId is provided, only entries for that period are summed.
     * When $month is provided (Y-m), only entries in that month are summed (legacy behavior).
     * When both are null, all-time for the tenant is summed.
     */
    public function getTotalForUser(
        int $tenantId,
        string $userIdentifier,
        ?int $scorePeriodId = null,
        ?string $month = null
    ): int {
        $query = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier);

        if ($scorePeriodId !== null) {
            $query->where('score_period_id', $scorePeriodId);
        }
        if ($month !== null) {
            $start = $month.'-01 00:00:00';
            $end = now()->parse($start)->endOfMonth()->format('Y-m-d 23:59:59');
            $query->whereBetween('created_at', [$start, $end]);
        }

        return (int) $query->sum('points_delta');
    }
}

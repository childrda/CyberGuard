<?php

namespace App\Services\Gamification;

use App\Models\ScorePeriod;
use App\Models\ShieldPointsLedger;
use App\Models\UserBadge;

/**
 * Engagement metrics and progress for individual users:
 * points, rank, badges, points history by period.
 */
class ProgressService
{
    public function __construct(
        protected LeaderboardService $leaderboard,
        protected PointsService $points
    ) {}

    /**
     * Full progress snapshot for one user: points, rank, badges, and optional breakdown.
     *
     * @return array{
     *   total_points: int,
     *   rank_in_tenant: int|null,
     *   total_users: int,
     *   period_label: string|null,
     *   badges: array<int, array{id: int, name: string, slug: string, awarded_at: string}>,
     *   points_by_event_type: array<string, int>
     * }
     */
    public function getForUser(
        int $tenantId,
        string $userIdentifier,
        ?int $scorePeriodId = null
    ): array {
        $period = $scorePeriodId
            ? ScorePeriod::withoutGlobalScope('tenant')->find($scorePeriodId)
            : ScorePeriod::currentForTenant($tenantId);

        $totalPoints = $this->points->getTotalForUser($tenantId, $userIdentifier, $scorePeriodId, null);
        $rankData = $this->leaderboard->individualRank($tenantId, $userIdentifier, $scorePeriodId);

        $badges = UserBadge::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier)
            ->with('badge:id,name,slug')
            ->orderByDesc('awarded_at')
            ->get()
            ->map(fn ($ub) => [
                'id' => $ub->badge_id,
                'name' => $ub->badge?->name ?? '',
                'slug' => $ub->badge?->slug ?? '',
                'awarded_at' => $ub->awarded_at->toIso8601String(),
            ])
            ->values()
            ->all();

        $pointsByEventType = $this->getPointsBreakdownByEventType($tenantId, $userIdentifier, $scorePeriodId);

        return [
            'total_points' => $totalPoints,
            'rank_in_tenant' => $rankData['rank'] ?? null,
            'total_users' => $rankData['total_users'] ?? 0,
            'period_label' => $period?->name,
            'badges' => $badges,
            'points_by_event_type' => $pointsByEventType,
        ];
    }

    /**
     * Points breakdown by event_type for the user (e.g. simulation_reported => 50, clicked => -10).
     *
     * @return array<string, int>
     */
    public function getPointsBreakdownByEventType(
        int $tenantId,
        string $userIdentifier,
        ?int $scorePeriodId = null
    ): array {
        $query = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier);

        if ($scorePeriodId !== null) {
            $query->where('score_period_id', $scorePeriodId);
        }

        $rows = $query
            ->selectRaw('event_type, SUM(points_delta) as total')
            ->groupBy('event_type')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[$row->event_type] = (int) $row->total;
        }
        return $out;
    }

    /**
     * Points history for the user over the last N score periods (for trend / improvement).
     * Returns one entry per period; periods with no activity have 0 points.
     *
     * @return array<int, array{period_id: int, period_name: string, period_slug: string, start_date: string, end_date: string, points: int}>
     */
    public function getPointsHistoryByPeriod(
        int $tenantId,
        string $userIdentifier,
        int $numberOfPeriods = 6
    ): array {
        $periods = ScorePeriod::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('end_date')
            ->limit($numberOfPeriods)
            ->get();

        if ($periods->isEmpty()) {
            return [];
        }

        $totals = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier)
            ->whereIn('score_period_id', $periods->pluck('id'))
            ->selectRaw('score_period_id, SUM(points_delta) as points')
            ->groupBy('score_period_id')
            ->get()
            ->keyBy('score_period_id');

        $result = [];
        foreach ($periods as $period) {
            $result[] = [
                'period_id' => $period->id,
                'period_name' => $period->name,
                'period_slug' => $period->slug,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
                'points' => (int) ($totals->get($period->id)?->points ?? 0),
            ];
        }
        return $result;
    }

    /**
     * Points history by calendar month (legacy-style, when score periods are not used).
     *
     * @return array<int, array{month: string, year: int, points: int}>
     */
    public function getPointsHistoryByMonth(
        int $tenantId,
        string $userIdentifier,
        int $numberOfMonths = 6
    ): array {
        $result = [];
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            $points = $this->points->getTotalForUser($tenantId, $userIdentifier, null, $month);
            $result[] = [
                'month' => $month,
                'year' => (int) $date->format('Y'),
                'points' => $points,
            ];
        }
        return array_reverse($result);
    }
}

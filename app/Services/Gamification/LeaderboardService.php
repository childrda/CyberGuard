<?php

namespace App\Services\Gamification;

use App\Models\ShieldPointsLedger;

/**
 * Leaderboard queries based on shield_points_ledger.
 * Supports tenant-wide, by OU, by department, and individual rank.
 */
class LeaderboardService
{
    /**
     * Tenant-wide leaderboard: top users by total points.
     *
     * @return array<int, array{rank: int, user_identifier: string, total_points: int}>
     */
    public function tenantLeaderboard(
        int $tenantId,
        ?int $scorePeriodId = null,
        int $limit = 50
    ): array {
        $query = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if ($scorePeriodId !== null) {
            $query->where('score_period_id', $scorePeriodId);
        }

        $rows = $query
            ->selectRaw('user_identifier, SUM(points_delta) as total_points')
            ->groupBy('user_identifier')
            ->orderByDesc('total_points')
            ->orderBy('user_identifier')
            ->limit($limit)
            ->get();

        return $this->assignRanks($rows->toArray(), 'user_identifier', 'total_points');
    }

    /**
     * Leaderboard aggregated by department (uses users.department; ledger joined to users by email).
     * Only ledger entries whose user_identifier matches a user with that tenant are included.
     *
     * @return array<int, array{rank: int, department: string, total_points: int}>
     */
    public function departmentLeaderboard(
        int $tenantId,
        ?int $scorePeriodId = null,
        int $limit = 50
    ): array {
        $base = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('shield_points_ledger.tenant_id', $tenantId);

        if ($scorePeriodId !== null) {
            $base->where('shield_points_ledger.score_period_id', $scorePeriodId);
        }

        $rows = $base
            ->join('users', function ($join) use ($tenantId) {
                $join->on('users.email', '=', 'shield_points_ledger.user_identifier')
                    ->where('users.tenant_id', '=', $tenantId);
            })
            ->selectRaw('COALESCE(NULLIF(TRIM(users.department), ""), "Unassigned") as department, SUM(shield_points_ledger.points_delta) as total_points')
            ->groupBy('department')
            ->orderByDesc('total_points')
            ->orderBy('department')
            ->limit($limit)
            ->get();

        return $this->assignRanks($rows->toArray(), 'department', 'total_points');
    }

    /**
     * Leaderboard aggregated by OU (uses users.ou). Same join logic as department.
     *
     * @return array<int, array{rank: int, ou: string, total_points: int}>
     */
    public function ouLeaderboard(
        int $tenantId,
        ?int $scorePeriodId = null,
        int $limit = 50
    ): array {
        $base = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('shield_points_ledger.tenant_id', $tenantId);

        if ($scorePeriodId !== null) {
            $base->where('shield_points_ledger.score_period_id', $scorePeriodId);
        }

        $rows = $base
            ->join('users', function ($join) use ($tenantId) {
                $join->on('users.email', '=', 'shield_points_ledger.user_identifier')
                    ->where('users.tenant_id', '=', $tenantId);
            })
            ->selectRaw('COALESCE(NULLIF(TRIM(users.ou), ""), "Unassigned") as ou, SUM(shield_points_ledger.points_delta) as total_points')
            ->groupBy('ou')
            ->orderByDesc('total_points')
            ->orderBy('ou')
            ->limit($limit)
            ->get();

        return $this->assignRanks($rows->toArray(), 'ou', 'total_points');
    }

    /**
     * Top users within a specific department.
     *
     * @return array<int, array{rank: int, user_identifier: string, total_points: int}>
     */
    public function usersInDepartmentLeaderboard(
        int $tenantId,
        string $department,
        ?int $scorePeriodId = null,
        int $limit = 50
    ): array {
        $base = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('shield_points_ledger.tenant_id', $tenantId)
            ->join('users', function ($join) use ($tenantId) {
                $join->on('users.email', '=', 'shield_points_ledger.user_identifier')
                    ->where('users.tenant_id', '=', $tenantId);
            });

        $departmentNormalized = trim($department);
        if ($departmentNormalized === '') {
            $base->where(function ($q) {
                $q->whereNull('users.department')->orWhere('users.department', '');
            });
        } else {
            $base->where('users.department', $departmentNormalized);
        }

        if ($scorePeriodId !== null) {
            $base->where('shield_points_ledger.score_period_id', $scorePeriodId);
        }

        $rows = $base
            ->selectRaw('shield_points_ledger.user_identifier, SUM(shield_points_ledger.points_delta) as total_points')
            ->groupBy('shield_points_ledger.user_identifier')
            ->orderByDesc('total_points')
            ->orderBy('shield_points_ledger.user_identifier')
            ->limit($limit)
            ->get();

        return $this->assignRanks($rows->toArray(), 'user_identifier', 'total_points');
    }

    /**
     * Top users within a specific OU.
     *
     * @return array<int, array{rank: int, user_identifier: string, total_points: int}>
     */
    public function usersInOuLeaderboard(
        int $tenantId,
        string $ou,
        ?int $scorePeriodId = null,
        int $limit = 50
    ): array {
        $base = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('shield_points_ledger.tenant_id', $tenantId)
            ->join('users', function ($join) use ($tenantId) {
                $join->on('users.email', '=', 'shield_points_ledger.user_identifier')
                    ->where('users.tenant_id', '=', $tenantId);
            });

        $ouNormalized = trim($ou);
        if ($ouNormalized === '') {
            $base->where(function ($q) {
                $q->whereNull('users.ou')->orWhere('users.ou', '');
            });
        } else {
            $base->where('users.ou', $ouNormalized);
        }

        if ($scorePeriodId !== null) {
            $base->where('shield_points_ledger.score_period_id', $scorePeriodId);
        }

        $rows = $base
            ->selectRaw('shield_points_ledger.user_identifier, SUM(shield_points_ledger.points_delta) as total_points')
            ->groupBy('shield_points_ledger.user_identifier')
            ->orderByDesc('total_points')
            ->orderBy('shield_points_ledger.user_identifier')
            ->limit($limit)
            ->get();

        return $this->assignRanks($rows->toArray(), 'user_identifier', 'total_points');
    }

    /**
     * Get an individual user's rank and total points in the tenant (all users).
     *
     * @return array{rank: int, total_points: int, total_users: int}|null Null if user has no ledger entries.
     */
    public function individualRank(
        int $tenantId,
        string $userIdentifier,
        ?int $scorePeriodId = null
    ): ?array {
        $query = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if ($scorePeriodId !== null) {
            $query->where('score_period_id', $scorePeriodId);
        }

        $userTotal = (int) (clone $query)
            ->where('user_identifier', $userIdentifier)
            ->sum('points_delta');

        $totals = (clone $query)
            ->selectRaw('user_identifier, SUM(points_delta) as total_points')
            ->groupBy('user_identifier')
            ->orderByDesc('total_points')
            ->orderBy('user_identifier')
            ->get();

        $totalUsers = $totals->count();
        $rank = null;
        $pos = 1;
        foreach ($totals as $row) {
            if ($row->user_identifier === $userIdentifier) {
                $rank = $pos;
                break;
            }
            $pos++;
        }

        if ($rank === null && $userTotal === 0) {
            return null;
        }
        if ($rank === null) {
            $rank = $pos;
        }

        return [
            'rank' => $rank,
            'total_points' => $userTotal,
            'total_users' => $totalUsers,
        ];
    }

    /**
     * @param  array<int, object|array>  $rows
     * @param  string  $keyColumn  e.g. user_identifier, department, ou
     * @param  string  $pointsColumn  e.g. total_points
     * @return array<int, array{rank: int}&array<string, mixed>>
     */
    private function assignRanks(array $rows, string $keyColumn, string $pointsColumn): array
    {
        $out = [];
        $rank = 1;
        foreach ($rows as $row) {
            $arr = is_array($row) ? $row : (array) $row;
            $arr['rank'] = $rank++;
            $arr['total_points'] = (int) ($arr[$pointsColumn] ?? 0);
            $out[] = $arr;
        }
        return $out;
    }
}

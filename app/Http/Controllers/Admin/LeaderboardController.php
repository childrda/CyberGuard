<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScorePeriod;
use App\Models\Tenant;
use App\Services\Gamification\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        protected LeaderboardService $leaderboard
    ) {}

    public function index(Request $request): View
    {
        $tenant = Tenant::current();
        $tenantId = $tenant?->id;
        $gamificationEnabled = $tenant?->gamification_enabled ?? false;
        $allowedPerPage = [10, 20, 40, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $scope = $request->input('scope', 'tenant'); // tenant, department, ou
        $periodInput = $request->input('period');
        $scorePeriodId = ($periodInput !== null && $periodInput !== '') ? (int) $periodInput : null;

        $periods = $tenantId
            ? ScorePeriod::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->orderByDesc('end_date')->limit(20)->get()
            : collect();

        $leaderboard = [];
        $periodLabel = 'All time';
        if ($tenantId !== null && $gamificationEnabled) {
            if ($scorePeriodId) {
                $p = $periods->firstWhere('id', $scorePeriodId);
                $periodLabel = $p ? $p->name : 'Period #'.$scorePeriodId;
            }
            $limit = 200;
            if ($scope === 'department') {
                $leaderboard = $this->leaderboard->departmentLeaderboard($tenantId, $scorePeriodId, $limit);
            } elseif ($scope === 'ou') {
                $leaderboard = $this->leaderboard->ouLeaderboard($tenantId, $scorePeriodId, $limit);
            } else {
                $leaderboard = $this->leaderboard->tenantLeaderboard($tenantId, $scorePeriodId, $limit);
            }
        }

        $page = max(1, (int) $request->input('page', 1));
        $total = count($leaderboard);
        $items = array_slice($leaderboard, ($page - 1) * $perPage, $perPage);
        $leaderboardPaginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.leaderboard.index', [
            'leaderboard' => $leaderboardPaginator,
            'scope' => $scope,
            'periods' => $periods,
            'scorePeriodId' => $scorePeriodId,
            'periodLabel' => $periodLabel,
            'gamificationEnabled' => $gamificationEnabled,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScorePeriod;
use App\Models\Tenant;
use App\Services\Gamification\LeaderboardService;
use Illuminate\Http\Request;
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
            $limit = 50;
            if ($scope === 'department') {
                $leaderboard = $this->leaderboard->departmentLeaderboard($tenantId, $scorePeriodId, $limit);
            } elseif ($scope === 'ou') {
                $leaderboard = $this->leaderboard->ouLeaderboard($tenantId, $scorePeriodId, $limit);
            } else {
                $leaderboard = $this->leaderboard->tenantLeaderboard($tenantId, $scorePeriodId, $limit);
            }
        }

        return view('admin.leaderboard.index', [
            'leaderboard' => $leaderboard,
            'scope' => $scope,
            'periods' => $periods,
            'scorePeriodId' => $scorePeriodId,
            'periodLabel' => $periodLabel,
            'gamificationEnabled' => $gamificationEnabled,
        ]);
    }
}

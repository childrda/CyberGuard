<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShieldPointsLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = $month.'-01 00:00:00';
        $end = now()->parse($start)->endOfMonth()->format('Y-m-d 23:59:59');

        $leaderboard = ShieldPointsLedger::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('user_identifier, SUM(points_delta) as total_points')
            ->groupBy('user_identifier')
            ->orderByDesc('total_points')
            ->limit(50)
            ->get();

        return view('admin.leaderboard.index', [
            'leaderboard' => $leaderboard,
            'month' => $month,
        ]);
    }
}

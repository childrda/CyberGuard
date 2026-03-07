<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PhishingCampaign;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\ReportedMessage;
use App\Models\RemediationJob;
use App\Models\ShieldPointsLedger;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->input('range', 30);
        $days = (int) $range;
        if ($days && in_array($days, [7, 30, 90], true)) {
            $dateFrom = now()->subDays($days)->toDateString();
            $dateTo = now()->toDateString();
        } else {
            $dateFrom = $request->input('from', now()->subDays(30)->toDateString());
            $dateTo = $request->input('to', now()->toDateString());
        }
        $dateRange = $range == 30 ? 'Past 30 Days' : ($range == 7 ? 'Past 7 Days' : ($range == 90 ? 'Past 90 Days' : $dateFrom.' to '.$dateTo));

        $campaignIds = Tenant::currentId() !== null
            ? PhishingCampaign::pluck('id')
            : [];

        $activeCampaigns = PhishingCampaign::whereIn('status', ['sending', 'scheduled', 'approved'])->count();
        $sentCount = $campaignIds !== []
            ? PhishingMessage::whereIn('campaign_id', $campaignIds)->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])->count()
            : 0;
        $deliveredCount = $campaignIds !== []
            ? PhishingMessage::whereIn('campaign_id', $campaignIds)->where('status', 'sent')->whereBetween('sent_at', [$dateFrom, $dateTo.' 23:59:59'])->count()
            : 0;
        $uniqueClicks = $campaignIds !== []
            ? PhishingEvent::where('event_type', 'clicked')
                ->whereIn('message_id', PhishingMessage::whereIn('campaign_id', $campaignIds)->pluck('id'))
                ->whereBetween('occurred_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->distinct('message_id')
                ->count('message_id')
            : 0;
        $reportCount = $campaignIds !== []
            ? PhishingEvent::where('event_type', 'reported')
                ->whereIn('message_id', PhishingMessage::whereIn('campaign_id', $campaignIds)->pluck('id'))
                ->whereBetween('occurred_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->count()
            : 0;
        $submissionCount = $campaignIds !== []
            ? PhishingEvent::where('event_type', 'submitted')
                ->whereIn('message_id', PhishingMessage::whereIn('campaign_id', $campaignIds)->pluck('id'))
                ->whereBetween('occurred_at', [$dateFrom, $dateTo.' 23:59:59'])
                ->count()
            : 0;

        $reportRate = $sentCount > 0 ? (int) round(($reportCount / $sentCount) * 100) : 0;
        $threatsRemoved = RemediationJob::whereNotNull('completed_at')
            ->whereIn('status', [RemediationJob::STATUS_REMOVED, RemediationJob::STATUS_PARTIALLY_FAILED])
            ->sum('removed_count');

        $monthStart = now()->format('Y-m').'-01 00:00:00';
        $monthEnd = now()->endOfMonth()->format('Y-m-d 23:59:59');
        $topReporters = ShieldPointsLedger::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('user_identifier, SUM(points_delta) as total_points')
            ->groupBy('user_identifier')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();
        $topReporter = $topReporters->first();

        $recentCampaigns = PhishingCampaign::with('template')->latest()->take(5)->get();
        $recentReports = ReportedMessage::latest()->take(8)->get();
        $latestRemediationJob = RemediationJob::with(['items', 'reportedMessage'])->latest()->first();
        $recentAuditLogs = AuditLog::with('user')->orderByDesc('created_at')->take(8)->get();

        $campaignActivity = PhishingCampaign::with('template')
            ->whereBetween('created_at', [$dateFrom, $dateTo.' 23:59:59'])
            ->get()
            ->map(fn ($c) => [
                'name' => $c->name,
                'count' => PhishingMessage::where('campaign_id', $c->id)->count(),
            ])
            ->sortByDesc('count')
            ->take(4)
            ->values();

        return view('admin.dashboard', [
            'activeCampaigns' => $activeCampaigns,
            'sentCount' => $sentCount,
            'deliveredCount' => $deliveredCount,
            'uniqueClicks' => $uniqueClicks,
            'reportCount' => $reportCount,
            'submissionCount' => $submissionCount,
            'reportRate' => $reportRate,
            'threatsRemoved' => $threatsRemoved,
            'topReporter' => $topReporter,
            'topReporters' => $topReporters,
            'recentCampaigns' => $recentCampaigns,
            'recentReports' => $recentReports,
            'latestRemediationJob' => $latestRemediationJob,
            'recentAuditLogs' => $recentAuditLogs,
            'campaignActivity' => $campaignActivity,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateRange' => $dateRange,
        ]);
    }
}

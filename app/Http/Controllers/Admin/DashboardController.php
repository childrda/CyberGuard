<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingCampaign;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\ReportedMessage;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('to', now()->toDateString());

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

        $recentCampaigns = PhishingCampaign::with('template')->latest()->take(5)->get();
        $recentReports = ReportedMessage::latest()->take(10)->get();

        return view('admin.dashboard', [
            'activeCampaigns' => $activeCampaigns,
            'sentCount' => $sentCount,
            'deliveredCount' => $deliveredCount,
            'uniqueClicks' => $uniqueClicks,
            'reportCount' => $reportCount,
            'submissionCount' => $submissionCount,
            'recentCampaigns' => $recentCampaigns,
            'recentReports' => $recentReports,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}

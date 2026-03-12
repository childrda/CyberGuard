@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-white">Welcome back, {{ auth()->user()->name }}.</h1>
    <form method="get" class="flex items-center gap-2" id="dashboard-range-form">
        <select name="range" onchange="this.form.submit()" class="rounded border-slate-600 bg-slate-800 text-slate-200 text-sm py-1.5 px-3">
            <option value="7" @selected(request('range') === '7')>Past 7 Days</option>
            <option value="30" @selected(request('range', '30') === '30')>Past 30 Days</option>
            <option value="90" @selected(request('range') === '90')>Past 90 Days</option>
        </select>
    </form>
</div>

{{-- Metric cards --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-8">
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-blue-500/20 p-2">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            </div>
            <div>
                <p class="text-sm text-slate-400">Emails Sent</p>
                <p class="text-2xl font-bold text-white">{{ number_format($sentCount) }}</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-emerald-500/20 p-2">
                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <div>
                <p class="text-sm text-slate-400" title="Reports of simulation emails ÷ emails sent (selected period)">Report Rate</p>
                <p class="text-2xl font-bold text-white">{{ $reportRate }}%</p>
                <p class="text-xs text-slate-500">simulation reports only</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-lg bg-amber-500/20 p-2">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </div>
            <div>
                <p class="text-sm text-slate-400">Threats Removed</p>
                <p class="text-2xl font-bold text-white">{{ number_format($threatsRemoved) }}</p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 p-5">
        <div class="flex items-center gap-3">
            <div class="rounded-full h-10 w-10 bg-slate-600 flex items-center justify-center text-sm font-semibold text-slate-200">
                {{ $topReporter ? strtoupper(substr($topReporter->user_identifier, 0, 2)) : '—' }}
            </div>
            <div>
                <p class="text-sm text-slate-400">Top Reporter</p>
                <p class="text-lg font-bold text-white truncate">{{ $topReporter ? $topReporter->user_identifier : '—' }}</p>
                @if($topReporter)
                    <p class="text-xs text-slate-500">{{ number_format($topReporter->total_points) }} points</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2 mb-6">
    {{-- Recent Reports --}}
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-700 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-200">Recent Reports</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-400 hover:text-blue-300">Approve All</a>
                <a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-400 hover:text-blue-300">Remove from Mailboxes</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-700">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-400">Subject</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-400">Sender</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-400">Reported By</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-400">Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($recentReports as $r)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-2 text-sm"><a href="{{ route('admin.reports.show', $r) }}" class="text-blue-400 hover:underline truncate max-w-[140px] block">{{ $r->subject ?? 'No subject' }}</a></td>
                            <td class="px-4 py-2 text-sm text-slate-300 truncate max-w-[120px]">{{ $r->from_address ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-slate-300">{{ $r->reporter_email ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-slate-400">{{ $r->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-2">
                                @if($r->analyst_status === 'analyst_confirmed_real')
                                    <span class="rounded px-2 py-0.5 text-xs font-medium bg-red-500/20 text-red-300">Confirmed</span>
                                @elseif($r->analyst_status === 'false_positive')
                                    <span class="rounded px-2 py-0.5 text-xs font-medium bg-slate-500/20 text-slate-400">Dismissed</span>
                                @else
                                    <span class="rounded px-2 py-0.5 text-xs font-medium bg-amber-500/20 text-amber-300">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500 text-sm">No reports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 border-t border-slate-700"><a href="{{ route('admin.reports.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View all reports</a></div>
    </div>

    {{-- Active Campaigns (bar chart) --}}
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-200">Active Campaigns</h2>
            <span class="text-xs text-slate-400">{{ $dateRange }}</span>
        </div>
        <div class="p-4 space-y-3">
            @php $maxCount = $campaignActivity->max('count') ?: 1; @endphp
            @forelse($campaignActivity as $c)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-300 truncate">{{ $c['name'] }}</span>
                        <span class="text-slate-400">{{ number_format($c['count']) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-700 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, ($c['count'] / $maxCount) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 py-4">No campaign activity in this period.</p>
            @endforelse
        </div>
        <div class="px-4 py-2 border-t border-slate-700"><a href="{{ route('admin.campaigns.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View all campaigns</a></div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Remediation Job --}}
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-700">
            <h2 class="text-sm font-semibold text-slate-200">Remediation Job @if($latestRemediationJob)#{{ $latestRemediationJob->id }}@endif</h2>
        </div>
        <div class="p-4">
            @if($latestRemediationJob)
                <p class="text-sm text-slate-400 mb-3">
                    Identical messages found in {{ $latestRemediationJob->items->count() }} mailbox(es)
                </p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm mb-3">
                    <span class="text-emerald-400">Successfully removed: {{ $latestRemediationJob->removed_count ?? 0 }}</span>
                    @if(($latestRemediationJob->dry_run_count ?? 0) > 0)
                        <span class="text-slate-400">Simulated: {{ $latestRemediationJob->dry_run_count }}</span>
                    @endif
                    @if(($latestRemediationJob->failed_count ?? 0) > 0)
                        <span class="text-red-400">Failed: {{ $latestRemediationJob->failed_count }}</span>
                    @endif
                    @if(($latestRemediationJob->skipped_count ?? 0) > 0)
                        <span class="text-slate-500">Skipped: {{ $latestRemediationJob->skipped_count }}</span>
                    @endif
                    @if(in_array($latestRemediationJob->status, [\App\Models\RemediationJob::STATUS_REMOVAL_IN_PROGRESS, \App\Models\RemediationJob::STATUS_APPROVED_FOR_REMOVAL]))
                        <span class="text-amber-400">Pending</span>
                    @endif
                </div>
                <ul class="space-y-2 max-h-32 overflow-y-auto">
                    @foreach($latestRemediationJob->items->take(5) as $item)
                        <li class="flex items-center gap-2 text-xs">
                            <span class="rounded-full h-6 w-6 bg-slate-600 flex items-center justify-center text-slate-300 flex-shrink-0">{{ strtoupper(substr($item->mailbox_email ?? '?', 0, 1)) }}</span>
                            <span class="text-slate-400 truncate">{{ $item->mailbox_email }}</span>
                            <span class="rounded px-1.5 py-0.5 text-slate-500 {{ $item->status === 'success' ? 'bg-emerald-500/20 text-emerald-400' : ($item->status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-slate-600 text-slate-400') }}">{{ $item->status }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('admin.remediation.show', $latestRemediationJob) }}" class="mt-3 inline-block text-sm text-blue-400 hover:text-blue-300">View job</a>
            @else
                <p class="text-sm text-slate-500">No remediation jobs yet.</p>
                <a href="{{ route('admin.remediation.index') }}" class="mt-2 inline-block text-sm text-blue-400 hover:text-blue-300">Remediation</a>
            @endif
        </div>
    </div>

    @if($gamificationEnabled ?? true)
    {{-- Top Reporters --}}
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-700">
            <h2 class="text-sm font-semibold text-slate-200">Top Reporters</h2>
        </div>
        <div class="p-4">
            @forelse($topReporters as $rep)
                <div class="flex items-center justify-between py-2 border-b border-slate-700/50 last:border-0">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full h-8 w-8 bg-slate-600 flex items-center justify-center text-sm font-medium text-slate-200">{{ strtoupper(substr($rep->user_identifier, 0, 2)) }}</span>
                        <span class="text-sm text-slate-200 truncate">{{ $rep->user_identifier }}</span>
                    </div>
                    <span class="text-sm font-semibold text-amber-400">{{ number_format($rep->total_points) }} pts</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No reporter points in this period.</p>
            @endforelse
        </div>
        <div class="px-4 py-2 border-t border-slate-700"><a href="{{ route('admin.leaderboard.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View all</a></div>
    </div>
    @endif

    {{-- Audit Log --}}
    <div class="rounded-xl border border-slate-700 bg-slate-800/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-200">Audit Log</h2>
            @php $currentTenant = \App\Models\Tenant::current(); @endphp
            @if($currentTenant)
                <span class="text-xs text-slate-400">{{ $currentTenant->name }}</span>
            @endif
        </div>
        <ul class="p-4 space-y-2 max-h-64 overflow-y-auto">
            @forelse($recentAuditLogs as $log)
                <li class="text-xs text-slate-400 flex gap-2">
                    <span class="text-slate-200 font-medium">{{ $log->user?->name ?? 'System' }}</span>
                    <span>{{ $log->action }}</span>
                    <span class="text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                </li>
            @empty
                <li class="text-slate-500">No audit entries.</li>
            @endforelse
        </ul>
        <div class="px-4 py-2 border-t border-slate-700"><a href="{{ route('admin.audit.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View audit logs</a></div>
    </div>
</div>
@endsection

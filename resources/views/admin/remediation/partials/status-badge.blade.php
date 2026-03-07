@php
    $badgeClass = match($job->status ?? '') {
        \App\Models\RemediationJob::STATUS_REMOVED => 'bg-emerald-500/20 text-emerald-300',
        \App\Models\RemediationJob::STATUS_DRY_RUN_COMPLETED => 'bg-slate-500/20 text-slate-300',
        \App\Models\RemediationJob::STATUS_PARTIALLY_FAILED => 'bg-amber-500/20 text-amber-300',
        \App\Models\RemediationJob::STATUS_FAILED => 'bg-red-500/20 text-red-300',
        \App\Models\RemediationJob::STATUS_REMOVAL_IN_PROGRESS => 'bg-blue-500/20 text-blue-300',
        \App\Models\RemediationJob::STATUS_APPROVED_FOR_REMOVAL => 'bg-emerald-500/20 text-emerald-300',
        default => 'bg-slate-500/20 text-slate-400',
    };
@endphp
<span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $job->statusLabel() }}</span>

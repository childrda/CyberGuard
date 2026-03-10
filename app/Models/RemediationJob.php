<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemediationJob extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED_FOR_REMOVAL = 'approved_for_removal';
    public const STATUS_REMOVAL_IN_PROGRESS = 'removal_in_progress';
    public const STATUS_REMOVED = 'removed';
    public const STATUS_PARTIALLY_FAILED = 'partially_failed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DRY_RUN_COMPLETED = 'dry_run_completed';

    protected $fillable = [
        'tenant_id',
        'reported_message_id',
        'correlation_id',
        'status',
        'dry_run',
        'approved_by',
        'approved_at',
        'approval_notes',
        'started_at',
        'completed_at',
        'failure_summary',
        'removed_count',
        'skipped_count',
        'dry_run_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'removed_count' => 'integer',
            'skipped_count' => 'integer',
            'dry_run_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_REVIEW => 'Pending review',
            self::STATUS_APPROVED_FOR_REMOVAL => 'Approved',
            self::STATUS_REMOVAL_IN_PROGRESS => 'In progress',
            self::STATUS_REMOVED => 'Removed',
            self::STATUS_PARTIALLY_FAILED => 'Partially failed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_DRY_RUN_COMPLETED => 'Dry run (simulated)',
            default => $this->status,
        };
    }

    public function reportedMessage(): BelongsTo
    {
        return $this->belongsTo(ReportedMessage::class, 'reported_message_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RemediationJobItem::class, 'remediation_job_id');
    }

    public function mailboxActionLogs(): HasMany
    {
        return $this->hasMany(MailboxActionLog::class, 'remediation_job_id');
    }
}

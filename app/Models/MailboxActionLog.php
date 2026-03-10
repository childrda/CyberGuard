<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'remediation_job_id',
        'remediation_job_item_id',
        'mailbox_email',
        'message_identifier',
        'action_attempted',
        'action_result',
        'actor_id',
        'actor_type',
        'api_response_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function remediationJob(): BelongsTo
    {
        return $this->belongsTo(RemediationJob::class);
    }

    public function remediationJobItem(): BelongsTo
    {
        return $this->belongsTo(RemediationJobItem::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

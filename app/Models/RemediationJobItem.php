<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RemediationJobItem extends Model
{
    protected $fillable = [
        'remediation_job_id',
        'mailbox_email',
        'gmail_message_id',
        'message_identifier',
        'status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function remediationJob(): BelongsTo
    {
        return $this->belongsTo(RemediationJob::class);
    }

    public function mailboxActionLogs(): HasMany
    {
        return $this->hasMany(MailboxActionLog::class, 'remediation_job_item_id');
    }
}

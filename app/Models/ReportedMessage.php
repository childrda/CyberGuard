<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportedMessage extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'correlation_id',
        'reporter_email',
        'reporter_name',
        'gmail_message_id',
        'gmail_thread_id',
        'subject',
        'from_address',
        'from_display',
        'to_addresses',
        'message_date',
        'snippet',
        'headers',
        'message_id_header',
        'report_type',
        'source',
        'phishing_message_id',
        'analyst_status',
        'analyst_id',
        'analyst_reviewed_at',
        'analyst_notes',
        'user_actions',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'user_actions' => 'array',
            'message_date' => 'datetime',
            'analyst_reviewed_at' => 'datetime',
        ];
    }

    public function phishingMessage(): BelongsTo
    {
        return $this->belongsTo(PhishingMessage::class, 'phishing_message_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PhishingReport::class, 'reported_message_id');
    }

    public function remediationJobs(): HasMany
    {
        return $this->hasMany(RemediationJob::class, 'reported_message_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhishingMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'attack_id',
        'recipient_email',
        'recipient_name',
        'tracking_token',
        'message_id',
        'status',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PhishingCampaign::class, 'campaign_id');
    }

    public function attack(): BelongsTo
    {
        return $this->belongsTo(PhishingAttack::class, 'attack_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PhishingEvent::class, 'message_id');
    }

    public function reportedMessage(): HasMany
    {
        return $this->hasMany(ReportedMessage::class, 'phishing_message_id');
    }
}

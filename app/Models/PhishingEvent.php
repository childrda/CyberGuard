<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhishingEvent extends Model
{
    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'message_id',
        'event_type',
        'ip_address',
        'user_agent',
        'referer',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PhishingMessage::class, 'message_id');
    }
}

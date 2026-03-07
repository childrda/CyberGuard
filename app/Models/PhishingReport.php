<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhishingReport extends Model
{
    protected $fillable = ['reported_message_id', 'message_id', 'event_type', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function reportedMessage(): BelongsTo
    {
        return $this->belongsTo(ReportedMessage::class, 'reported_message_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(PhishingMessage::class, 'message_id');
    }
}

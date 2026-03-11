<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCompletion extends Model
{
    protected $fillable = ['phishing_message_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function phishingMessage(): BelongsTo
    {
        return $this->belongsTo(PhishingMessage::class, 'phishing_message_id');
    }
}

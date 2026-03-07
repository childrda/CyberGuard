<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShieldPointsLedger extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public $timestamps = false;

    protected $table = 'shield_points_ledger';

    protected $fillable = [
        'tenant_id',
        'user_identifier',
        'user_id',
        'event_type',
        'points_delta',
        'reason',
        'campaign_id',
        'reported_message_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PhishingCampaign::class, 'campaign_id');
    }

    public function reportedMessage(): BelongsTo
    {
        return $this->belongsTo(ReportedMessage::class, 'reported_message_id');
    }
}

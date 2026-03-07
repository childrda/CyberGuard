<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhishingCampaignTarget extends Model
{
    protected $fillable = ['campaign_id', 'target_type', 'target_identifier', 'display_name', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PhishingCampaign::class, 'campaign_id');
    }
}

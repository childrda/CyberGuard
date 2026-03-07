<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhishingCampaign extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'template_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'send_window_start_minute',
        'send_window_end_minute',
        'throttle_per_minute',
        'randomize_send_times',
        'allowed_domains',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'allowed_domains' => 'array',
            'randomize_send_times' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PhishingTemplate::class, 'template_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PhishingCampaignTarget::class, 'campaign_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PhishingMessage::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

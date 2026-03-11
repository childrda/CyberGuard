<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Challenge extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'score_period_id',
        'goal_type',
        'goal_config',
        'start_at',
        'end_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'goal_config' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scorePeriod(): BelongsTo
    {
        return $this->belongsTo(ScorePeriod::class, 'score_period_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'criteria_type',
        'criteria_config',
        'icon',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'criteria_config' => 'array',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }
}

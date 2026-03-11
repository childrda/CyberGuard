<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttackAsset extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'attack_id',
        'filename',
        'original_name',
        'mime_type',
        'storage_path',
        'public_url',
        'width',
        'height',
        'created_by',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attack(): BelongsTo
    {
        return $this->belongsTo(PhishingAttack::class, 'attack_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * URL to use in email HTML (storage link or public_url).
     */
    public function getUrlAttribute(): string
    {
        if ($this->public_url) {
            return $this->public_url;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->storage_path);
    }
}

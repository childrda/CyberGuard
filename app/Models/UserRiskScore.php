<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRiskScore extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    protected $fillable = ['tenant_id', 'email', 'department', 'ou', 'score', 'factors', 'calculated_at'];

    protected function casts(): array
    {
        return [
            'factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }
}

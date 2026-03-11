<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['tenant_id', 'type', 'message', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function log(string $type, string $message, array $context = []): self
    {
        return self::create([
            'tenant_id' => Tenant::currentId(),
            'type' => $type,
            'message' => $message,
            'context' => $context ?: null,
        ]);
    }
}

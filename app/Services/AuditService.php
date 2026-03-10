<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $correlationId = null
    ): AuditLog {
        $tenantId = \App\Models\Tenant::currentId();
        return AuditLog::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl(),
            'request_method' => Request::method(),
            'created_at' => now(),
        ]);
    }
}

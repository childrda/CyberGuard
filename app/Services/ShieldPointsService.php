<?php

namespace App\Services;

use App\Models\ShieldPointsLedger;
use App\Models\Tenant;

class ShieldPointsService
{
    public function award(
        int $tenantId,
        string $userIdentifier,
        string $eventType,
        int $pointsDelta,
        ?string $reason = null,
        ?int $campaignId = null,
        ?int $reportedMessageId = null,
        ?int $userId = null
    ): ShieldPointsLedger {
        return ShieldPointsLedger::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'user_identifier' => $userIdentifier,
            'user_id' => $userId,
            'event_type' => $eventType,
            'points_delta' => $pointsDelta,
            'reason' => $reason,
            'campaign_id' => $campaignId,
            'reported_message_id' => $reportedMessageId,
            'created_at' => now(),
        ]);
    }

    public function getTotalForUser(int $tenantId, string $userIdentifier, ?string $month = null): int
    {
        $query = ShieldPointsLedger::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier);
        if ($month) {
            $start = $month.'-01 00:00:00';
            $end = now()->parse($start)->endOfMonth()->format('Y-m-d 23:59:59');
            $query->whereBetween('created_at', [$start, $end]);
        }
        return (int) $query->sum('points_delta');
    }
}

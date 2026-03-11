<?php

namespace App\Services\Gamification;

use App\Models\Badge;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\ReportedMessage;
use App\Models\ScorePeriod;
use App\Models\UserBadge;
use Carbon\Carbon;

/**
 * Evaluates badge criteria against phishing_events and reported_messages,
 * and awards badges by writing to user_badges. Idempotent per user/badge.
 */
class BadgeService
{
    /**
     * Evaluate all active badges for the tenant and award any that the user
     * now qualifies for (and has not already received).
     */
    public function evaluateForUser(
        int $tenantId,
        string $userIdentifier,
        ?int $scorePeriodId = null
    ): array {
        $awarded = [];
        $badges = Badge::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($badges as $badge) {
            if ($this->hasBadge($tenantId, $userIdentifier, $badge->id)) {
                continue;
            }
            if (! $this->meetsCriteria($badge, $tenantId, $userIdentifier)) {
                continue;
            }
            $userBadge = $this->awardBadge($tenantId, $userIdentifier, $badge, $scorePeriodId);
            $awarded[] = $userBadge;
        }

        return $awarded;
    }

    /**
     * Check whether the user has already been awarded this badge.
     */
    public function hasBadge(int $tenantId, string $userIdentifier, int $badgeId): bool
    {
        return UserBadge::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('user_identifier', $userIdentifier)
            ->where('badge_id', $badgeId)
            ->exists();
    }

    /**
     * Evaluate badge criteria against events and reports.
     */
    public function meetsCriteria(Badge $badge, int $tenantId, string $userIdentifier): bool
    {
        return match ($badge->criteria_type) {
            'first_report' => $this->getCorrectReportsCount($tenantId, $userIdentifier) >= 1,
            'reports_count' => $this->meetsReportsCountCriteria($badge, $tenantId, $userIdentifier),
            'no_click_streak_days' => $this->meetsNoClickStreakCriteria($badge, $tenantId, $userIdentifier),
            default => false,
        };
    }

    private function meetsReportsCountCriteria(Badge $badge, int $tenantId, string $userIdentifier): bool
    {
        $minReports = (int) ($badge->criteria_config['min_reports'] ?? 0);
        if ($minReports < 1) {
            return false;
        }
        return $this->getCorrectReportsCount($tenantId, $userIdentifier) >= $minReports;
    }

    private function meetsNoClickStreakCriteria(Badge $badge, int $tenantId, string $userIdentifier): bool
    {
        $requiredDays = (int) ($badge->criteria_config['days'] ?? 30);
        if ($requiredDays < 1) {
            return false;
        }
        $lastClickAt = $this->getLastClickAt($tenantId, $userIdentifier);
        if ($lastClickAt === null) {
            return true; // never clicked
        }
        $cutoff = now()->subDays($requiredDays);

        return $lastClickAt->lt($cutoff);
    }

    /**
     * Count of correct reports (reports that matched a simulation) by this user in this tenant.
     */
    public function getCorrectReportsCount(int $tenantId, string $userIdentifier): int
    {
        return ReportedMessage::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('reporter_email', $userIdentifier)
            ->whereNotNull('phishing_message_id')
            ->count();
    }

    /**
     * Last time this user (as recipient) clicked a phishing simulation link in this tenant.
     * Returns null if they have never clicked.
     */
    public function getLastClickAt(int $tenantId, string $userIdentifier): ?Carbon
    {
        $messageIds = PhishingMessage::query()
            ->where('recipient_email', $userIdentifier)
            ->whereHas('campaign', fn ($q) => $q->where('tenant_id', $tenantId))
            ->pluck('id');

        if ($messageIds->isEmpty()) {
            return null;
        }

        $occurred = PhishingEvent::query()
            ->where('event_type', 'clicked')
            ->whereIn('message_id', $messageIds)
            ->max('occurred_at');

        return $occurred ? Carbon::parse($occurred) : null;
    }

    /**
     * Award a badge to the user. Idempotent: uses firstOrCreate so duplicate calls do not create duplicates.
     */
    public function awardBadge(
        int $tenantId,
        string $userIdentifier,
        Badge $badge,
        ?int $scorePeriodId = null,
        ?int $userId = null
    ): UserBadge {
        $scorePeriodId = $scorePeriodId ?? ScorePeriod::currentForTenant($tenantId)?->id;

        return UserBadge::withoutGlobalScope('tenant')->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_identifier' => $userIdentifier,
                'badge_id' => $badge->id,
            ],
            [
                'user_id' => $userId,
                'score_period_id' => $scorePeriodId,
                'awarded_at' => now(),
            ]
        );
    }
}

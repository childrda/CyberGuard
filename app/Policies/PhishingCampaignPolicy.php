<?php

namespace App\Policies;

use App\Models\PhishingCampaign;
use App\Models\User;

class PhishingCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst', 'viewer']);
    }

    public function view(User $user, PhishingCampaign $campaign): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }

    public function update(User $user, PhishingCampaign $campaign): bool
    {
        if (! $user->hasAnyRole(['superadmin', 'campaign_admin'])) {
            return false;
        }
        // Allow edit while draft, paused, approved, or scheduled (before/during setup). Not while sending or completed.
        return in_array($campaign->status, ['draft', 'paused', 'approved', 'scheduled']);
    }

    public function delete(User $user, PhishingCampaign $campaign): bool
    {
        return $user->hasRole('superadmin') && $campaign->status === 'draft';
    }

    public function approve(User $user, PhishingCampaign $campaign): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin'])
            && in_array($campaign->status, ['draft', 'pending_approval']);
    }

    public function launch(User $user, PhishingCampaign $campaign): bool
    {
        return $user->canLaunchCampaigns()
            && in_array($campaign->status, ['approved', 'scheduled', 'paused'])
            && config('phishing.simulation_enabled', false);
    }

    /**
     * Cancel a launched campaign so it can be edited (including targets) and re-approved/launched.
     */
    public function cancel(User $user, PhishingCampaign $campaign): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin'])
            && in_array($campaign->status, ['sending', 'completed', 'scheduled', 'approved']);
    }
}

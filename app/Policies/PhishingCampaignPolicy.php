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
        return in_array($campaign->status, ['draft', 'paused']);
    }

    public function delete(User $user, PhishingCampaign $campaign): bool
    {
        return $user->hasRole('superadmin') && $campaign->status === 'draft';
    }

    public function approve(User $user, PhishingCampaign $campaign): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin'])
            && $campaign->status === 'pending_approval';
    }

    public function launch(User $user, PhishingCampaign $campaign): bool
    {
        return $user->canLaunchCampaigns()
            && in_array($campaign->status, ['approved', 'scheduled', 'paused'])
            && config('phishing.simulation_enabled', false);
    }
}

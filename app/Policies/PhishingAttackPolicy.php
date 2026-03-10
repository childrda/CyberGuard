<?php

namespace App\Policies;

use App\Models\PhishingAttack;
use App\Models\User;

class PhishingAttackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst', 'viewer']);
    }

    public function view(User $user, PhishingAttack $attack): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }

    public function update(User $user, PhishingAttack $attack): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }

    public function delete(User $user, PhishingAttack $attack): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }
}

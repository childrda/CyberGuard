<?php

namespace App\Policies;

use App\Models\PhishingTemplate;
use App\Models\User;

class PhishingTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst', 'viewer']);
    }

    public function view(User $user, PhishingTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }

    public function update(User $user, PhishingTemplate $template): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }

    public function delete(User $user, PhishingTemplate $template): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin']);
    }
}

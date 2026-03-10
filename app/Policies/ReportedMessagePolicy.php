<?php

namespace App\Policies;

use App\Models\ReportedMessage;
use App\Models\User;

class ReportedMessagePolicy
{
    public function view(User $user, ReportedMessage $reported): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst', 'viewer']);
    }

    public function updateStatus(User $user, ReportedMessage $reported): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst']);
    }

    public function removeFromMailbox(User $user, ReportedMessage $reported): bool
    {
        return $user->hasAnyRole(['superadmin', 'campaign_admin', 'analyst'])
            && $reported->analyst_status === 'analyst_confirmed_real';
    }
}

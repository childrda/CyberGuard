<?php

namespace Database\Seeders;

use App\Models\PhishingCampaign;
use App\Models\PhishingCampaignTarget;
use App\Models\PhishingTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhishingCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = Tenant::where('domain', 'example.com')->first()?->id;
        $template = PhishingTemplate::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->first();
        $user = User::first();
        if (! $template || ! $user) {
            return;
        }

        $campaign = PhishingCampaign::withoutGlobalScope('tenant')->firstOrCreate(
            ['name' => 'Sample campaign', 'tenant_id' => $tenantId],
            [
                'tenant_id' => $tenantId,
                'template_id' => $template->id,
                'status' => 'draft',
                'created_by' => $user->id,
            ]
        );

        PhishingCampaignTarget::firstOrCreate(
            ['campaign_id' => $campaign->id, 'target_type' => 'user', 'target_identifier' => 'viewer@example.com'],
            ['display_name' => 'Viewer User']
        );
    }
}

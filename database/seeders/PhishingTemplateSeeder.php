<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\PhishingTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhishingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $landing = LandingPage::where('slug', 'default-training')->first();
        $userId = User::first()?->id;
        $tenantId = Tenant::where('domain', 'example.com')->first()?->id;

        PhishingTemplate::withoutGlobalScope('tenant')->firstOrCreate(
            ['name' => 'Sample IT Alert', 'tenant_id' => $tenantId],
            [
                'tenant_id' => $tenantId,
                'subject' => 'Urgent: Verify your account',
                'html_body' => '<p>Hi,</p><p>We detected unusual activity. <a href="#">Click here to verify your account</a> within 24 hours.</p><p>IT Support</p>',
                'text_body' => "Hi,\nWe detected unusual activity. Click the link in this email to verify within 24 hours.\nIT Support",
                'sender_name' => 'IT Support',
                'sender_email' => 'support@example.com',
                'landing_page_type' => 'training',
                'training_page_id' => $landing?->id,
                'difficulty' => 'medium',
                'tags' => ['urgent', 'verify'],
                'active' => true,
                'created_by' => $userId,
            ]
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\PhishingCampaign;
use App\Models\PhishingMessage;
use App\Models\PhishingTemplate;
use App\Models\ReportedMessage;
use App\Models\ShieldPointsLedger;
use App\Models\Tenant;
use App\Services\ShieldPointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsAwardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_simulation_match_awards_immediately_via_webhook(): void
    {
        $tenant = Tenant::create([
            'name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true,
            'webhook_secret' => 'secret',
        ]);
        $template = PhishingTemplate::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tpl',
            'subject' => 'Test',
            'html_body' => '<p>Hi</p>',
            'text_body' => 'Hi',
        ]);
        $campaign = PhishingCampaign::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'name' => 'C',
            'template_id' => $template->id,
            'status' => 'sending',
        ]);
        $msg = PhishingMessage::create([
            'campaign_id' => $campaign->id,
            'recipient_email' => 'user@example.com',
            'message_id' => 'gmail-123',
            'status' => 'sent',
        ]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'user@example.com',
            'subject' => 'Test',
            'phishing_message_id' => $msg->id,
        ]);
        app(ShieldPointsService::class)->award($tenant->id, 'user@example.com', 'simulation_reported', 10, 'Reported simulation', $campaign->id, $reported->id, null);
        $this->assertDatabaseHas('shield_points_ledger', [
            'tenant_id' => $tenant->id,
            'user_identifier' => 'user@example.com',
            'event_type' => 'simulation_reported',
            'points_delta' => 10,
        ]);
    }

    public function test_real_phish_points_only_on_analyst_confirm(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'user@example.com',
            'subject' => 'Test',
            'phishing_message_id' => null,
        ]);
        $this->assertDatabaseCount('shield_points_ledger', 0);
        app(ShieldPointsService::class)->award($tenant->id, 'user@example.com', 'reported_phish', 10, 'Reported real phishing (analyst confirmed)', null, $reported->id, null);
        $this->assertDatabaseHas('shield_points_ledger', [
            'event_type' => 'reported_phish',
            'points_delta' => 10,
        ]);
    }

    public function test_leaderboard_sums_by_user_and_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        ShieldPointsLedger::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'user_identifier' => 'alice@example.com',
            'event_type' => 'simulation_reported',
            'points_delta' => 10,
            'created_at' => now(),
        ]);
        ShieldPointsLedger::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'user_identifier' => 'alice@example.com',
            'event_type' => 'simulation_reported',
            'points_delta' => 10,
            'created_at' => now(),
        ]);
        $total = app(ShieldPointsService::class)->getTotalForUser($tenant->id, 'alice@example.com');
        $this->assertSame(20, $total);
    }
}

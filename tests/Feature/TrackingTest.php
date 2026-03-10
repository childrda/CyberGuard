<?php

namespace Tests\Feature;

use App\Models\PhishingCampaign;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\PhishingTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\RoleSeeder::class, \Database\Seeders\LandingPageSeeder::class]);
    }

    public function test_click_tracking_logs_event_and_redirects(): void
    {
        $template = PhishingTemplate::factory()->create();
        $campaign = PhishingCampaign::factory()->create(['template_id' => $template->id]);
        $message = PhishingMessage::factory()->create([
            'campaign_id' => $campaign->id,
            'tracking_token' => 'test-token-123',
        ]);

        $response = $this->get('/t/test-token-123');

        $response->assertRedirect();
        $this->assertDatabaseHas('phishing_events', [
            'message_id' => $message->id,
            'event_type' => 'clicked',
        ]);
    }

    public function test_invalid_token_redirects(): void
    {
        $response = $this->get('/t/invalid-token-xyz');
        $response->assertRedirect();
    }
}

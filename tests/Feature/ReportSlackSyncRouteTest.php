<?php

namespace Tests\Feature;

use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportSlackSyncRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_analyst_can_push_slack_sync_immediately_from_report(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'channel' => 'CXYZ',
                'ts' => '199.888',
            ]),
        ]);

        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 'slack-btn-'.uniqid(),
            'active' => true,
            'slack_alerts_enabled' => true,
            'slack_bot_token' => 'xoxb-test',
            'slack_channel' => 'alerts',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@cgtest.invalid',
            'subject' => 'Subj',
            'user_actions' => ['clicked_link'],
        ]);

        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.reports.sync-slack', $reported));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            $flat = json_encode($request['blocks'] ?? []);

            return str_contains($request->url(), 'chat.postMessage')
                && str_contains((string) $flat, 'Clicked a link');
        });
    }
}

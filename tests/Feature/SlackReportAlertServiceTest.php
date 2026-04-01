<?php

namespace Tests\Feature;

use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Services\SlackReportAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SlackReportAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_posts_new_slack_message_and_persists_reference(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant',
            'domain' => 'example.com',
            'slug' => 'tenant',
            'active' => true,
            'slack_alerts_enabled' => true,
            'slack_bot_token' => 'xoxb-test',
            'slack_channel' => 'phishing-alert',
        ]);

        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'user@example.com',
            'subject' => 'Suspicious mail',
            'from_address' => 'attacker@evil.test',
            'report_type' => 'phish',
        ]);

        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'channel' => 'C123',
                'ts' => '1712590674.12345',
            ]),
        ]);

        app(SlackReportAlertService::class)->syncReportAlert($reported->fresh('tenant'));

        $this->assertDatabaseHas('reported_messages', [
            'id' => $reported->id,
            'slack_channel' => 'C123',
            'slack_message_ts' => '1712590674.12345',
        ]);
    }

    public function test_sync_updates_existing_slack_message_when_reference_exists(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant',
            'domain' => 'example.com',
            'slug' => 'tenant',
            'active' => true,
            'slack_alerts_enabled' => true,
            'slack_bot_token' => 'xoxb-test',
            'slack_channel' => 'phishing-alert',
        ]);

        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'user@example.com',
            'subject' => 'Suspicious mail',
            'from_address' => 'attacker@evil.test',
            'report_type' => 'spam',
            'slack_channel' => 'C123',
            'slack_message_ts' => '1712590674.12345',
            'analyst_status' => 'false_positive',
        ]);

        Http::fake([
            'https://slack.com/api/chat.update' => Http::response([
                'ok' => true,
                'channel' => 'C123',
                'ts' => '1712590674.12345',
            ]),
        ]);

        app(SlackReportAlertService::class)->syncReportAlert($reported->fresh('tenant'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://slack.com/api/chat.update'
                && ($request['channel'] ?? null) === 'C123'
                && ($request['ts'] ?? null) === '1712590674.12345';
        });
    }

    public function test_sync_includes_reporter_self_report_and_remediation_in_blocks(): void
    {
        $tenant = Tenant::create([
            'name' => 'Tenant',
            'domain' => 'cgtest.invalid',
            'slug' => 'slack-flags-'.uniqid(),
            'active' => true,
            'slack_alerts_enabled' => true,
            'slack_bot_token' => 'xoxb-test',
            'slack_channel' => 'phishing-alert',
        ]);

        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'user@cgtest.invalid',
            'subject' => 'Suspicious mail',
            'from_address' => 'attacker@evil.test',
            'report_type' => 'phish',
            'user_actions' => ['clicked_link', 'entered_info'],
            'remediation_via_google_admin' => true,
            'reporter_mailbox_cleared_at' => now(),
        ]);

        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'channel' => 'C999',
                'ts' => '1712590674.99999',
            ]),
        ]);

        app(SlackReportAlertService::class)->syncReportAlert($reported->fresh('tenant'));

        Http::assertSent(function ($request) {
            $blocks = $request['blocks'] ?? [];
            $flat = json_encode($blocks);

            return str_contains((string) $flat, 'Reporter self-report')
                && str_contains((string) $flat, 'Clicked a link')
                && str_contains((string) $flat, 'Entered sensitive information')
                && str_contains((string) $flat, 'Google Admin investigation tool')
                && str_contains((string) $flat, 'recalled')
                && str_contains((string) $flat, 'High priority');
        });
    }
}


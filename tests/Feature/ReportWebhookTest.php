<?php

namespace Tests\Feature;

use App\Jobs\SyncReportedMessageToSlackJob;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function createTenantWithSecret(string $domain = 'example.com', string $secret = 'test-secret'): Tenant
    {
        return Tenant::create([
            'name' => 'Test',
            'domain' => $domain,
            'slug' => 'test',
            'webhook_secret' => $secret,
            'remediation_policy' => 'analyst_approval_required',
            'active' => true,
        ]);
    }

    public function test_webhook_rejects_unknown_tenant(): void
    {
        $payload = ['reporter_email' => 'user@unknown.org', 'report_type' => 'phish'];
        $body = json_encode($payload);
        $sig = 'sha256='.hash_hmac('sha256', $body, 'any-secret');

        $response = $this->call('POST', '/api/webhook/report', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PHISH_SIGNATURE' => $sig,
        ], $body);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Unknown tenant']);
    }

    public function test_webhook_requires_valid_signature(): void
    {
        $this->createTenantWithSecret('example.com', 'test-secret');
        $payload = ['reporter_email' => 'user@example.com', 'report_type' => 'phish'];
        $body = json_encode($payload);
        $wrongSig = 'sha256='.hash_hmac('sha256', $body, 'wrong-secret');

        $response = $this->postJson('/api/webhook/report', $payload, [
            'X-Phish-Signature' => $wrongSig,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_payload_when_tenant_exists(): void
    {
        Queue::fake();
        $this->createTenantWithSecret('example.com', 'test-secret');
        $payload = [
            'reporter_email' => 'user@example.com',
            'report_type' => 'phish',
            'subject' => 'Test',
            'from_address' => 'phish@evil.com',
        ];
        $body = json_encode($payload);
        $sig = 'sha256='.hash_hmac('sha256', $body, 'test-secret');

        $response = $this->call('POST', '/api/webhook/report', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PHISH_SIGNATURE' => $sig,
        ], $body);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'matched_simulation' => false]);
        $this->assertDatabaseHas('reported_messages', ['reporter_email' => 'user@example.com']);
        Queue::assertPushed(SyncReportedMessageToSlackJob::class);
    }
}

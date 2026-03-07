<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_webhook_requires_valid_signature(): void
    {
        $payload = ['reporter_email' => 'user@example.com', 'report_type' => 'phish'];
        $body = json_encode($payload);
        $wrongSig = 'sha256='.hash_hmac('sha256', $body, 'wrong-secret');

        $response = $this->postJson('/api/webhook/report', $payload, [
            'X-Phish-Signature' => $wrongSig,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_payload(): void
    {
        $payload = [
            'reporter_email' => 'user@example.com',
            'report_type' => 'phish',
            'subject' => 'Test',
            'from_address' => 'phish@evil.com',
        ];
        $body = json_encode($payload);
        $sig = 'sha256='.hash_hmac('sha256', $body, 'test-secret');

        $response = $this->withBody($body, 'application/json')
            ->withHeader('X-Phish-Signature', $sig)
            ->post('/api/webhook/report');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'matched_simulation' => false]);
        $this->assertDatabaseHas('reported_messages', ['reporter_email' => 'user@example.com']);
    }
}

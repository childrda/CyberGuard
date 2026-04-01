<?php

namespace Tests\Feature;

use App\Jobs\SyncReportedMessageToSlackJob;
use App\Models\ReportedMessage;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SlackReportDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_confirm_real_queues_slack_sync_job(): void
    {
        Queue::fake();

        $tenant = Tenant::create([
            'name' => 'Acme District',
            'domain' => 'acme.k12.us',
            'slug' => 'acme',
            'active' => true,
        ]);

        $analyst = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'analyst@acme.k12.us',
        ]);
        $analyst->roles()->attach(Role::where('name', 'analyst')->firstOrFail()->id);

        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'teacher@acme.k12.us',
            'subject' => 'Suspicious message',
            'from_address' => 'bad@evil.test',
        ]);

        $this->actingAs($analyst);
        $this->withSession(['current_tenant_id' => $tenant->id]);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.reports.confirm-real', $reported), ['analyst_notes' => 'Investigating']);
        $response->assertRedirect();

        Queue::assertPushed(SyncReportedMessageToSlackJob::class, fn ($job) => $job->reportedMessageId === $reported->id);
    }
}


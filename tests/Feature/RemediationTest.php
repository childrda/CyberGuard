<?php

namespace Tests\Feature;

use App\Jobs\ProcessRemediationJob;
use App\Models\RemediationJob;
use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GmailRemovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\FakeGmailRemovalService;

class RemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_report_only_tenant_cannot_approve_remediation(): void
    {
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'example.com',
            'slug' => 't',
            'active' => true,
            'remediation_policy' => 'report_only',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'analyst_status' => 'analyst_confirmed_real',
        ]);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);
        $response = $this->post(route('admin.remediation.approve', $reported));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('remediation_jobs', 0);
    }

    public function test_approved_dry_run_job_has_dry_run_flag(): void
    {
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'example.com',
            'slug' => 't',
            'active' => true,
            'remediation_policy' => 'analyst_approval_required',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'analyst_status' => 'analyst_confirmed_real',
        ]);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);
        $response = $this->post(route('admin.remediation.approve', $reported), ['dry_run' => '1']);
        $response->assertRedirect();
        $this->assertDatabaseHas('remediation_jobs', [
            'reported_message_id' => $reported->id,
            'dry_run' => true,
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
        ]);
    }

    public function test_remediation_run_requires_approved_job(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
        ]);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);
        $response = $this->post(route('admin.remediation.run', $reported));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_pure_dry_run_job_ends_as_dry_run_completed_and_does_not_increment_removed(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'message_id_header' => '<msg-123@example.com>',
        ]);
        $job = RemediationJob::create([
            'tenant_id' => $tenant->id,
            'reported_message_id' => $reported->id,
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
            'dry_run' => true,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->app->instance(GmailRemovalService::class, new FakeGmailRemovalService(
            ['a@example.com', 'b@example.com'],
            []
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_DRY_RUN_COMPLETED, $job->status);
        $this->assertSame(0, $job->removed_count);
        $this->assertSame(2, $job->dry_run_count);
        $this->assertSame(0, $job->failed_count);
    }

    public function test_real_successful_remediation_ends_as_removed(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'message_id_header' => '<msg-456@example.com>',
        ]);
        $job = RemediationJob::create([
            'tenant_id' => $tenant->id,
            'reported_message_id' => $reported->id,
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
            'dry_run' => false,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->app->instance(GmailRemovalService::class, new FakeGmailRemovalService(
            ['u1@example.com', 'u2@example.com'],
            ['u1@example.com' => ['ok' => true], 'u2@example.com' => ['ok' => true]]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_REMOVED, $job->status);
        $this->assertSame(2, $job->removed_count);
        $this->assertSame(0, $job->dry_run_count);
        $this->assertSame(0, $job->failed_count);
    }

    public function test_mixed_real_and_failed_actions_result_in_partially_failed(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'message_id_header' => '<msg-789@example.com>',
        ]);
        $job = RemediationJob::create([
            'tenant_id' => $tenant->id,
            'reported_message_id' => $reported->id,
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
            'dry_run' => false,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->app->instance(GmailRemovalService::class, new FakeGmailRemovalService(
            ['ok@example.com', 'fail@example.com'],
            [
                'ok@example.com' => ['ok' => true],
                'fail@example.com' => ['ok' => false, 'error' => 'Not found'],
            ]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_PARTIALLY_FAILED, $job->status);
        $this->assertSame(1, $job->removed_count);
        $this->assertSame(1, $job->failed_count);
    }

    public function test_all_failed_ends_as_failed(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@example.com',
            'subject' => 'Test',
            'message_id_header' => '<msg-999@example.com>',
        ]);
        $job = RemediationJob::create([
            'tenant_id' => $tenant->id,
            'reported_message_id' => $reported->id,
            'status' => RemediationJob::STATUS_APPROVED_FOR_REMOVAL,
            'dry_run' => false,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->app->instance(GmailRemovalService::class, new FakeGmailRemovalService(
            ['a@example.com', 'b@example.com'],
            [
                'a@example.com' => ['ok' => false, 'error' => 'Error 1'],
                'b@example.com' => ['ok' => false, 'error' => 'Error 2'],
            ]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_FAILED, $job->status);
        $this->assertSame(0, $job->removed_count);
        $this->assertSame(2, $job->failed_count);
    }
}

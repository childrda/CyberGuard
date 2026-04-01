<?php

namespace Tests\Feature;

use App\Jobs\ProcessRemediationJob;
use App\Models\RemediationJob;
use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GmailRemovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeGmailRemovalService;
use Tests\TestCase;

class RemediationTest extends TestCase
{
    use RefreshDatabase;

    private static ?string $sharedGoogleCredsPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function googleCredsPath(): string
    {
        if (self::$sharedGoogleCredsPath === null) {
            self::$sharedGoogleCredsPath = tempnam(sys_get_temp_dir(), 'cg-gc').'.json';
            file_put_contents(self::$sharedGoogleCredsPath, '{}');
        }

        return self::$sharedGoogleCredsPath;
    }

    public function test_report_only_tenant_cannot_approve_remediation(): void
    {
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-ro-'.uniqid(),
            'active' => true,
            'remediation_policy' => 'report_only',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@cgtest.invalid',
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
            'domain' => 'cgtest.invalid',
            'slug' => 't-ap-'.uniqid(),
            'active' => true,
            'remediation_policy' => 'analyst_approval_required',
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@cgtest.invalid',
            'subject' => 'Test',
            'analyst_status' => 'analyst_confirmed_real',
        ]);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);
        config(['phishing.gmail_removal_enabled' => true]);
        Queue::fake();

        $response = $this->post(route('admin.remediation.approve', $reported), ['dry_run' => '1']);
        $response->assertRedirect();
        $this->assertDatabaseHas('remediation_jobs', [
            'reported_message_id' => $reported->id,
            'dry_run' => true,
            'status' => RemediationJob::STATUS_REMOVAL_IN_PROGRESS,
        ]);
        Queue::assertPushed(ProcessRemediationJob::class, 1);
    }

    public function test_remediation_run_requires_approved_job(): void
    {
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-run-'.uniqid(),
            'active' => true,
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'u@cgtest.invalid',
            'subject' => 'Test',
        ]);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);
        config(['phishing.gmail_removal_enabled' => true]);
        $response = $this->post(route('admin.remediation.run', $reported));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_pure_dry_run_job_ends_as_dry_run_completed_and_does_not_increment_removed(): void
    {
        config(['phishing.gmail_removal_enabled' => true]);
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-dr-'.uniqid(),
            'active' => true,
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'reporter@cgtest.invalid',
            'subject' => 'Test',
            'message_id_header' => '<msg-123@cgtest.invalid>',
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
            [],
            []
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_DRY_RUN_COMPLETED, $job->status);
        $this->assertSame(0, $job->removed_count);
        $this->assertSame(1, $job->dry_run_count);
        $this->assertSame(0, $job->failed_count);
    }

    public function test_real_successful_remediation_ends_as_removed(): void
    {
        config(['phishing.gmail_removal_enabled' => true]);
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-ok-'.uniqid(),
            'active' => true,
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'reporter@cgtest.invalid',
            'subject' => 'Test',
            'message_id_header' => '<msg-456@cgtest.invalid>',
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
            [],
            ['reporter@cgtest.invalid' => ['ok' => true]]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_REMOVED, $job->status);
        $this->assertSame(1, $job->removed_count);
        $this->assertSame(0, $job->dry_run_count);
        $this->assertSame(0, $job->failed_count);
    }

    public function test_skipped_reporter_mailbox_ends_as_failed(): void
    {
        config(['phishing.gmail_removal_enabled' => true]);
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-sk-'.uniqid(),
            'active' => true,
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'reporter@cgtest.invalid',
            'subject' => 'Test',
            'message_id_header' => '<msg-789@cgtest.invalid>',
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
            [],
            ['reporter@cgtest.invalid' => ['ok' => true, 'skipped' => true]]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_FAILED, $job->status);
        $this->assertSame(0, $job->removed_count);
        $this->assertSame(1, $job->skipped_count);
        $this->assertSame(0, $job->failed_count);
    }

    public function test_all_failed_ends_as_failed(): void
    {
        config(['phishing.gmail_removal_enabled' => true]);
        $tenant = Tenant::create([
            'name' => 'T',
            'domain' => 'cgtest.invalid',
            'slug' => 't-fl-'.uniqid(),
            'active' => true,
            'google_credentials_path' => $this->googleCredsPath(),
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $approver = User::factory()->create(['tenant_id' => $tenant->id]);
        $reported = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenant->id,
            'reporter_email' => 'reporter@cgtest.invalid',
            'subject' => 'Test',
            'message_id_header' => '<msg-999@cgtest.invalid>',
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
            [],
            [
                'reporter@cgtest.invalid' => ['ok' => false, 'error' => 'Error 1'],
            ]
        ));

        (new ProcessRemediationJob($job))->handle();

        $job->refresh();
        $this->assertSame(RemediationJob::STATUS_FAILED, $job->status);
        $this->assertSame(0, $job->removed_count);
        $this->assertSame(1, $job->failed_count);
    }
}

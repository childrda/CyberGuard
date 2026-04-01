<?php

namespace Tests\Feature;

use App\Jobs\ProcessRemediationJob;
use App\Models\RemediationJob;
use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RoleEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function createReportedMessage(int $tenantId): ReportedMessage
    {
        return ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId,
            'reporter_email' => 'user@cgtest.invalid',
            'subject' => 'Test',
        ]);
    }

    public function test_viewer_cannot_confirm_real_phishing(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'cgtest.invalid', 'slug' => 're-v-'.uniqid(), 'active' => true]);
        $viewer = User::factory()->create(['tenant_id' => $tenant->id]);
        $viewer->roles()->attach(\App\Models\Role::where('name', 'viewer')->first()->id);

        $reported = $this->createReportedMessage($tenant->id);
        $this->actingAs($viewer);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.reports.confirm-real', $reported), ['analyst_notes' => '']);
        $response->assertStatus(403);
    }

    public function test_analyst_can_confirm_real_phishing(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'cgtest.invalid', 'slug' => 're-a-'.uniqid(), 'active' => true]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);

        $reported = $this->createReportedMessage($tenant->id);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.reports.confirm-real', $reported), ['analyst_notes' => '']);
        $response->assertRedirect();
        $reported->refresh();
        $this->assertSame('analyst_confirmed_real', $reported->analyst_status);
    }

    public function test_viewer_cannot_approve_remediation(): void
    {
        $tenant = Tenant::create([
            'name' => 'T', 'domain' => 'cgtest.invalid', 'slug' => 'rem-v-'.uniqid(), 'active' => true,
            'remediation_policy' => 'analyst_approval_required',
        ]);
        $viewer = User::factory()->create(['tenant_id' => $tenant->id]);
        $viewer->roles()->attach(\App\Models\Role::where('name', 'viewer')->first()->id);

        $reported = $this->createReportedMessage($tenant->id);
        $reported->update(['analyst_status' => 'analyst_confirmed_real']);
        $this->actingAs($viewer);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.remediation.approve', $reported), ['dry_run' => '1']);
        $response->assertStatus(403);
        $this->assertDatabaseCount('remediation_jobs', 0);
    }

    public function test_analyst_can_approve_remediation(): void
    {
        $creds = tempnam(sys_get_temp_dir(), 'cg-re').'.json';
        file_put_contents($creds, '{}');
        $tenant = Tenant::create([
            'name' => 'T', 'domain' => 'cgtest.invalid', 'slug' => 'rem-a-'.uniqid(), 'active' => true,
            'remediation_policy' => 'analyst_approval_required',
            'google_credentials_path' => $creds,
            'google_admin_user' => 'admin@cgtest.invalid',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);

        $reported = $this->createReportedMessage($tenant->id);
        $reported->update(['analyst_status' => 'analyst_confirmed_real']);
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
}

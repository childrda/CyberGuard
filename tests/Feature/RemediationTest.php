<?php

namespace Tests\Feature;

use App\Models\RemediationJob;
use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}

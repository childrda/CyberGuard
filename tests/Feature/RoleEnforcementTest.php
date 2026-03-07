<?php

namespace Tests\Feature;

use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'reporter_email' => 'user@example.com',
            'subject' => 'Test',
        ]);
    }

    public function test_viewer_cannot_confirm_real_phishing(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
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
        $tenant = Tenant::create(['name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true]);
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
            'name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true,
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
        $tenant = Tenant::create([
            'name' => 'T', 'domain' => 'example.com', 'slug' => 't', 'active' => true,
            'remediation_policy' => 'analyst_approval_required',
        ]);
        $analyst = User::factory()->create(['tenant_id' => $tenant->id]);
        $analyst->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);

        $reported = $this->createReportedMessage($tenant->id);
        $reported->update(['analyst_status' => 'analyst_confirmed_real']);
        $this->actingAs($analyst);
        app()->instance('current_tenant_id', $tenant->id);

        $response = $this->post(route('admin.remediation.approve', $reported), ['dry_run' => '1']);
        $response->assertRedirect();
        $this->assertDatabaseHas('remediation_jobs', [
            'reported_message_id' => $reported->id,
            'dry_run' => true,
            'status' => 'approved_for_removal',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ReportedMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_tenant_scoped_user_cannot_see_other_tenants_reports(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'domain' => 'a.com', 'slug' => 'a', 'active' => true]);
        $tenantB = Tenant::create(['name' => 'B', 'domain' => 'b.com', 'slug' => 'b', 'active' => true]);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userA->roles()->attach(\App\Models\Role::where('name', 'analyst')->first()->id);
        $reportInB = ReportedMessage::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantB->id,
            'reporter_email' => 'user@b.com',
            'subject' => 'B report',
        ]);
        $this->actingAs($userA);
        app()->instance('current_tenant_id', $tenantA->id);
        $response = $this->get(route('admin.reports.show', $reportInB));
        $response->assertStatus(404);
    }

    public function test_middleware_overrides_tampered_session_tenant_for_scoped_user(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'domain' => 'a.com', 'slug' => 'a', 'active' => true]);
        $tenantB = Tenant::create(['name' => 'B', 'domain' => 'b.com', 'slug' => 'b', 'active' => true]);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userA->roles()->attach(\App\Models\Role::where('name', 'viewer')->first()->id);
        $this->withSession(['current_tenant_id' => $tenantB->id])->actingAs($userA)->get(route('admin.reports.index'));
        $this->assertEquals($tenantA->id, session('current_tenant_id'));
        $this->assertEquals($tenantA->id, app('current_tenant_id'));
    }

    public function test_platform_admin_can_use_any_tenant_from_session(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'domain' => 't.com', 'slug' => 't', 'active' => true]);
        $admin = User::factory()->create(['tenant_id' => null]);
        $admin->roles()->attach(\App\Models\Role::where('name', 'superadmin')->first()->id);
        $this->withSession(['current_tenant_id' => $tenant->id])->actingAs($admin)->get(route('admin.reports.index'));
        $this->assertEquals($tenant->id, app('current_tenant_id'));
    }
}

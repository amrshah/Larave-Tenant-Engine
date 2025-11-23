<?php

namespace Amrshah\TenantEngine\Tests\Feature;

use Amrshah\TenantEngine\Models\SuperAdmin;
use Amrshah\TenantEngine\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    protected function actingAsSuperAdmin(): string
    {
        $admin = $this->createSuperAdmin();
        return $admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function super_admin_can_create_tenant(): void
    {
        $token = $this->actingAsSuperAdmin();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/super-admin/tenants', [
                'name' => 'Test Tenant',
                'email' => 'tenant@example.com',
                'slug' => 'test-tenant',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes',
                ],
            ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Tenant',
            'id' => 'test-tenant',
        ]);
    }

    /** @test */
    public function super_admin_can_list_tenants(): void
    {
        $token = $this->actingAsSuperAdmin();

        $tenantModel = config('tenancy.tenant_model');
        $tenantModel::create([
            'id' => 'tenant-1',
            'name' => 'Tenant 1',
            'email' => 'tenant1@example.com',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/super-admin/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
                'links',
            ]);
    }

    /** @test */
    public function super_admin_can_suspend_tenant(): void
    {
        $token = $this->actingAsSuperAdmin();

        $tenantModel = config('tenancy.tenant_model');
        $tenant = $tenantModel::create([
            'id' => 'tenant-1',
            'name' => 'Tenant 1',
            'email' => 'tenant1@example.com',
            'status' => 'active',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/super-admin/tenants/{$tenant->external_id}/suspend");

        $response->assertStatus(200);

        $this->assertDatabaseHas('tenants', [
            'id' => 'tenant-1',
            'status' => 'suspended',
        ]);
    }

    /** @test */
    public function regular_user_cannot_access_super_admin_routes(): void
    {
        $userModel = config('tenant-engine.models.user');
        
        $user = $userModel::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/super-admin/tenants');

        $response->assertStatus(403);
    }
}

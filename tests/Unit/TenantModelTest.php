<?php

namespace Amrshah\TenantEngine\Tests\Unit;

use Amrshah\TenantEngine\Models\Tenant;
use Amrshah\TenantEngine\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tenant_has_external_id_on_creation(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant', // Stancl uses string ID (slug)
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($tenant->external_id);
        $this->assertStringStartsWith('TNT_', $tenant->external_id);
    }

    /** @test */
    public function tenant_can_check_if_active(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->assertTrue($tenant->isActive());
        $this->assertFalse($tenant->isSuspended());
        $this->assertFalse($tenant->isCancelled());
    }

    /** @test */
    public function tenant_can_check_trial_status(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($tenant->isOnTrial());
        $this->assertFalse($tenant->trialHasEnded());
    }

    /** @test */
    public function tenant_trial_can_expire(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'trial_ends_at' => now()->subDays(1), // Trial ended yesterday
        ]);

        $this->assertFalse($tenant->isOnTrial());
        $this->assertTrue($tenant->trialHasEnded());
    }

    /** @test */
    public function tenant_can_be_suspended(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $result = $tenant->suspend();

        $this->assertTrue($result);
        $this->assertTrue($tenant->fresh()->isSuspended());
        $this->assertEquals('suspended', $tenant->fresh()->status);
    }

    /** @test */
    public function tenant_can_be_activated(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'status' => 'suspended',
        ]);

        $result = $tenant->activate();

        $this->assertTrue($result);
        $this->assertTrue($tenant->fresh()->isActive());
        $this->assertEquals('active', $tenant->fresh()->status);
    }

    /** @test */
    public function tenant_can_be_cancelled(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $result = $tenant->cancel();

        $this->assertTrue($result);
        $this->assertTrue($tenant->fresh()->isCancelled());
        $this->assertEquals('cancelled', $tenant->fresh()->status);
    }

    /** @test */
    public function tenant_can_be_found_by_external_id(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
        ]);

        $found = Tenant::findByExternalId($tenant->external_id);

        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
        $this->assertEquals($tenant->external_id, $found->external_id);
    }

    /** @test */
    public function tenant_can_check_subscription_status(): void
    {
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'subscription_ends_at' => now()->addMonths(1),
        ]);

        $this->assertTrue($tenant->hasActiveSubscription());
    }
}


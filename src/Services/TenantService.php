<?php

namespace Amrshah\TenantEngine\Services;

use Amrshah\TenantEngine\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantService
{
    /**
     * Create a new tenant with all related setup.
     */
    public function createTenant(array $data): Tenant
    {
        DB::beginTransaction();
        
        try {
            $tenant = Tenant::create([
                'id' => $data['slug'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'plan' => $data['plan'] ?? config('tenant-engine.tenant.default_plan'),
                'status' => config('tenant-engine.tenant.default_status'),
                'trial_ends_at' => isset($data['trial_days']) 
                    ? now()->addDays((int) $data['trial_days']) 
                    : null,
            ]);

            // Tenant database will be created automatically by Stancl
            
            Log::info('Tenant created successfully', [
                'tenant_id' => $tenant->id,
                'tenant_external_id' => $tenant->external_id,
                'plan' => $tenant->plan,
            ]);

            DB::commit();

            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            
            throw $e;
        }
    }

    /**
     * Update tenant information.
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
        ]));

        Log::info('Tenant updated', [
            'tenant_id' => $tenant->id,
            'updated_fields' => array_keys(array_filter($data)),
        ]);

        return $tenant->fresh();
    }

    /**
     * Suspend a tenant.
     */
    public function suspendTenant(Tenant $tenant, ?string $reason = null): Tenant
    {
        $tenant->suspend();

        Log::warning('Tenant suspended', [
            'tenant_id' => $tenant->id,
            'reason' => $reason,
            'suspended_by' => auth()->id(),
        ]);

        return $tenant;
    }

    /**
     * Activate a tenant.
     */
    public function activateTenant(Tenant $tenant): Tenant
    {
        $tenant->activate();

        Log::info('Tenant activated', [
            'tenant_id' => $tenant->id,
            'activated_by' => auth()->id(),
        ]);

        return $tenant;
    }

    /**
     * Cancel a tenant subscription.
     */
    public function cancelTenant(Tenant $tenant, ?string $reason = null): Tenant
    {
        $tenant->cancel();

        Log::warning('Tenant cancelled', [
            'tenant_id' => $tenant->id,
            'reason' => $reason,
            'cancelled_by' => auth()->id(),
        ]);

        return $tenant;
    }

    /**
     * Delete a tenant and optionally its database.
     */
    public function deleteTenant(Tenant $tenant, bool $deleteDatabase = false): bool
    {
        DB::beginTransaction();
        
        try {
            $tenantId = $tenant->id;
            
            if ($deleteDatabase && config('tenant-engine.tenant.delete_database_on_delete')) {
                // Database deletion will be handled by Stancl events
                Log::warning('Tenant database will be deleted', [
                    'tenant_id' => $tenantId,
                ]);
            }

            $tenant->delete();

            Log::warning('Tenant deleted', [
                'tenant_id' => $tenantId,
                'database_deleted' => $deleteDatabase,
                'deleted_by' => auth()->id(),
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Tenant deletion failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get tenant statistics.
     */
    public function getTenantStats(Tenant $tenant): array
    {
        return [
            'is_on_trial' => $tenant->isOnTrial(),
            'trial_has_ended' => $tenant->trialHasEnded(),
            'has_active_subscription' => $tenant->hasActiveSubscription(),
            'is_active' => $tenant->isActive(),
            'is_suspended' => $tenant->isSuspended(),
            'is_cancelled' => $tenant->isCancelled(),
            'days_until_trial_ends' => $tenant->trial_ends_at 
                ? now()->diffInDays($tenant->trial_ends_at, false) 
                : null,
        ];
    }

    /**
     * Get all tenants with filtering and pagination.
     */
    public function getTenants(array $filters = [], int $perPage = 15)
    {
        $query = Tenant::query();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('id', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }
}

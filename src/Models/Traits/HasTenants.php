<?php
/**
 * HasTenants Trait
 * 
 * Provides relationship to tenants for the User model.
 */

namespace Amrshah\TenantEngine\Models\Traits;

use Amrshah\TenantEngine\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasTenants
{
    /**
     * Get the tenants belonging to the user.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            config('tenant-engine.models.tenant', Tenant::class),
            'tenant_user',
            'user_id',
            'tenant_id'
        )->withPivot('role')->withTimestamps();
    }

    /**
     * Check if user has access to a specific tenant.
     */
    public function hasTenant(string $tenantId): bool
    {
        return $this->tenants()->where('tenants.id', $tenantId)->exists();
    }
}

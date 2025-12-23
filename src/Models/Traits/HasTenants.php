<?php

namespace Amrshah\TenantEngine\Models\Traits;

/**
 * Trait to handle tenant relationship for users.
 */
trait HasTenants
{
    /**
     * Get tenants belonging to this user.
     */
    public function tenants()
    {
        return $this->belongsToMany(
            config('tenant-engine.models.tenant'),
            'tenant_user',
            'user_id',
            'tenant_id'
        )->withPivot('role')->withTimestamps();
    }

    /**
     * Check if user is member of a tenant.
     */
    public function isMemberOf(string $tenantId): bool
    {
        return $this->tenants()->where('tenant_id', $tenantId)->exists();
    }
}

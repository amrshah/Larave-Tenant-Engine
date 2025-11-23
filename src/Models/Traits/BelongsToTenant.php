<?php

namespace Amrshah\TenantEngine\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\Syncable;

trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Automatically scope queries to current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = static::getCurrentTenantId()) {
                $builder->where(static::getTenantIdColumn(), $tenantId);
            }
        });

        // Automatically set tenant_id on creation
        static::creating(function ($model) {
            if (empty($model->{static::getTenantIdColumn()}) && $tenantId = static::getCurrentTenantId()) {
                $model->{static::getTenantIdColumn()} = $tenantId;
            }
        });
    }

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model'), static::getTenantIdColumn());
    }

    /**
     * Get current tenant ID from Stancl context
     */
    protected static function getCurrentTenantId(): ?string
    {
        // Use Stancl's tenant() helper
        return tenant('id');
    }

    /**
     * Get the tenant ID column name
     */
    protected static function getTenantIdColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Scope query without tenant filter
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant(Builder $query, $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where(static::getTenantIdColumn(), $tenantId);
    }

    /**
     * Check if model belongs to current tenant
     */
    public function belongsToCurrentTenant(): bool
    {
        $currentTenantId = static::getCurrentTenantId();
        
        if (!$currentTenantId) {
            return false;
        }

        return $this->{static::getTenantIdColumn()} === $currentTenantId;
    }

    /**
     * Check if model belongs to specific tenant
     */
    public function belongsToTenant($tenantId): bool
    {
        return $this->{static::getTenantIdColumn()} === $tenantId;
    }
}

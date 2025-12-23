<?php

namespace Amrshah\TenantEngine\Models;

use Amrshah\TenantEngine\Models\Traits\HasExternalId;
use Amrshah\TenantEngine\Traits\OptimizesQueries;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasExternalId, SoftDeletes, OptimizesQueries;

    /**
     * Allowed includes for eager loading.
     */
    protected array $allowedIncludes = ['domains', 'users'];

    /**
     * Allowed sort fields.
     */
    protected array $allowedSorts = ['name', 'email', 'created_at', 'status', 'plan'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'external_id',
        'id', // This is the slug
        'name',
        'email',
        'phone',
        'plan',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
        'data',
        'plan_id',
        'trial_ends_at',
    ];

    public function assignedPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'settings' => 'array',
        'data' => 'array',
    ];

    /**
     * Get custom columns for Stancl.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'external_id',
            'name',
            'email',
            'phone',
            'plan',
            'status',
            'trial_ends_at',
            'subscription_ends_at',
            'settings',
            'data',
        ];
    }

    /**
     * Get the external ID prefix.
     */
    protected static function getExternalIdPrefix(): string
    {
        return config('tenant-engine.external_id_prefixes.tenants', 'TNT');
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if tenant is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if tenant is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if trial has ended.
     */
    public function trialHasEnded(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Check if subscription is active.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    /**
     * Suspend the tenant.
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    /**
     * Activate the tenant.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Cancel the tenant.
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Get users belonging to this tenant.
     */
    public function users()
    {
        return $this->belongsToMany(
            config('tenant-engine.models.user') ?: config('auth.providers.users.model'),
            'tenant_user',
            'tenant_id',
            'user_id'
        )->withPivot('role')->withTimestamps();
    }

    /**
     * Scope to active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to suspended tenants.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope to cancelled tenants.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to tenants on trial.
     */
    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope to tenants by plan.
     */
    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }
}

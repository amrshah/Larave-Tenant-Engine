<?php

namespace Amrshah\TenantEngine\Models;

use Amrshah\TenantEngine\Models\Traits\HasExternalId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasExternalId;

    protected $connection = 'sqlite';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'external_id',
        'tenant_id',
        'user_type',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'resource_external_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the external ID prefix.
     */
    protected static function getExternalIdPrefix(): string
    {
        return config('tenant-engine.external_id_prefixes.audit_logs', 'AUD');
    }

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->morphTo();
    }

    /**
     * Get the tenant this log belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }

    /**
     * Scope to specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to current tenant.
     */
    public function scopeCurrentTenant($query)
    {
        if ($tenantId = tenant('id')) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Scope to specific user.
     */
    public function scopeForUser($query, $userId, ?string $userType = null)
    {
        $query->where('user_id', $userId);

        if ($userType) {
            $query->where('user_type', $userType);
        }

        return $query;
    }

    /**
     * Scope to specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to specific resource type.
     */
    public function scopeResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope to specific resource.
     */
    public function scopeResource($query, string $resourceType, $resourceId)
    {
        return $query->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId);
    }

    /**
     * Scope to date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get changes made.
     */
    public function getChanges(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Check if this is a create action.
     */
    public function isCreate(): bool
    {
        return $this->action === 'create' || $this->action === 'created';
    }

    /**
     * Check if this is an update action.
     */
    public function isUpdate(): bool
    {
        return $this->action === 'update' || $this->action === 'updated';
    }

    /**
     * Check if this is a delete action.
     */
    public function isDelete(): bool
    {
        return $this->action === 'delete' || $this->action === 'deleted';
    }
}

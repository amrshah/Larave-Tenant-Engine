<?php

namespace Amrshah\TenantEngine\Models;

use Amrshah\TenantEngine\Models\Traits\HasExternalId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasExternalId, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'external_id',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'last_login_at',
        'last_login_ip',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
    ];

    /**
     * Get the external ID prefix.
     */
    protected static function getExternalIdPrefix(): string
    {
        return config('tenant-engine.external_id_prefixes.super_admins', 'SAD');
    }

    /**
     * Check if super admin is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if super admin is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Activate the super admin.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Suspend the super admin.
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    /**
     * Update last login information.
     */
    public function updateLastLogin(?string $ip = null): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Check if super admin can impersonate users.
     */
    public function canImpersonate(): bool
    {
        return config('tenant-engine.super_admin.impersonation.enabled', true) 
            && $this->isActive();
    }

    /**
     * Scope to active super admins.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to suspended super admins.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Get audit logs for this super admin.
     */
    public function auditLogs()
    {
        return $this->morphMany(
            config('tenant-engine.models.audit_log'),
            'user'
        );
    }
}

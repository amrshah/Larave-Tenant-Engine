<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'super-admins',
            'id' => $this->external_id,
            'attributes' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?? null,
                'status' => $this->status,
                'last_login_at' => $this->last_login_at?->toIso8601String(),
                'last_login_ip' => $this->last_login_ip ?? null,
                'email_verified_at' => $this->email_verified_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            'meta' => [
                'is_active' => $this->isActive(),
                'can_impersonate' => $this->canImpersonate(),
            ],
            'links' => [
                'self' => url("/api/v1/super-admin/admins/{$this->external_id}"),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'jsonapi' => [
                'version' => '1.1',
            ],
        ];
    }
}

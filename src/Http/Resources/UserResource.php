<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'users',
            'id' => $this->external_id,
            'attributes' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?? null,
                'role' => $this->pivot ? $this->pivot->role : null,
                'email_verified_at' => $this->email_verified_at?->toIso8601String(),
                'last_login_at' => $this->last_login_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            'relationships' => $this->when($request->has('include'), function () use ($request) {
                $includes = explode(',', $request->input('include', ''));
                $relationships = [];

                if (in_array('tenants', $includes)) {
                    $tenants = $this->tenants ?? collect();
                    $relationships['tenants'] = [
                        'data' => $tenants->map(fn($tenant) => [
                            'type' => 'tenants',
                            'id' => $tenant->external_id,
                        ]),
                    ];
                }

                if (in_array('roles', $includes)) {
                    $roles = $this->roles ?? collect();
                    $relationships['roles'] = [
                        'data' => $roles->map(fn($role) => [
                            'type' => 'roles',
                            'id' => $role->id,
                        ]),
                    ];
                }

                return $relationships;
            }),
            'links' => [
                'self' => url("/api/v1/users/{$this->external_id}"),
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

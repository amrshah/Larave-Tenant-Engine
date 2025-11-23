<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'roles',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'display_name' => $this->display_name ?? $this->name,
                'description' => $this->description ?? null,
                'guard_name' => $this->guard_name ?? 'web',
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            'relationships' => $this->when($request->has('include'), function () use ($request) {
                $includes = explode(',', $request->input('include', ''));
                $relationships = [];

                if (in_array('permissions', $includes)) {
                    $relationships['permissions'] = [
                        'data' => $this->permissions->map(fn($permission) => [
                            'type' => 'permissions',
                            'id' => $permission->id,
                        ]),
                        'meta' => [
                            'count' => $this->permissions->count(),
                        ],
                    ];
                }

                return $relationships;
            }),
            'meta' => [
                'permissions_count' => $this->permissions->count(),
            ],
            'links' => [
                'self' => url("/{tenant}/api/v1/roles/{$this->id}"),
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

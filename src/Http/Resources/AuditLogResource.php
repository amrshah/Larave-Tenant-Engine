<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'audit-logs',
            'id' => $this->external_id,
            'attributes' => [
                'action' => $this->action,
                'resource_type' => $this->resource_type,
                'resource_id' => $this->resource_id,
                'resource_external_id' => $this->resource_external_id,
                'description' => $this->description,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'created_at' => $this->created_at->toIso8601String(),
            ],
            'meta' => [
                'is_create' => $this->isCreate(),
                'is_update' => $this->isUpdate(),
                'is_delete' => $this->isDelete(),
                'has_changes' => !empty($this->getChanges()),
            ],
            'relationships' => $this->when($request->has('include'), function () use ($request) {
                $includes = explode(',', $request->input('include', ''));
                $relationships = [];

                if (in_array('user', $includes) && $this->user) {
                    $relationships['user'] = [
                        'data' => [
                            'type' => 'users',
                            'id' => $this->user->external_id ?? $this->user->id,
                        ],
                    ];
                }

                if (in_array('tenant', $includes) && $this->tenant) {
                    $relationships['tenant'] = [
                        'data' => [
                            'type' => 'tenants',
                            'id' => $this->tenant->external_id,
                        ],
                    ];
                }

                return $relationships;
            }),
            'links' => [
                'self' => url("/api/v1/audit-logs/{$this->external_id}"),
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

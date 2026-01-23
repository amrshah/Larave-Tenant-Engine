<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'tenants',
            'id' => $this->external_id,
            'attributes' => [
                'name' => $this->name,
                'slug' => $this->id,
                'email' => $this->email,
                'phone' => $this->phone ?? null,
                'plan' => $this->plan,
                'status' => $this->status,
                'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
                'subscription_ends_at' => $this->subscription_ends_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            'meta' => [
                'is_on_trial' => $this->isOnTrial(),
                'trial_has_ended' => $this->trialHasEnded(),
                'has_active_subscription' => $this->hasActiveSubscription(),
                'is_active' => $this->isActive(),
            ],
            'relationships' => $this->when($request->has('include'), function () use ($request) {
                $includes = explode(',', $request->input('include', ''));
                $relationships = [];

                if (in_array('users', $includes)) {
                    $relationships['users'] = [
                        'data' => $this->users->map(fn($user) => [
                            'type' => 'users',
                            'id' => $user->external_id,
                        ]),
                        'meta' => [
                            'count' => $this->users->count(),
                        ],
                    ];
                }

                return $relationships;
            }),
            'links' => [
                'self' => url("/api/v1/super-admin/tenants/{$this->external_id}"),
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

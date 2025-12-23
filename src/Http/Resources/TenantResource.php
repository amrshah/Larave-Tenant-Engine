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
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'external_id' => $this->external_id,
                'slug' => $this->id,
                'email' => $this->email,
                'phone' => $this->phone ?? null,

                'plan' => $this->whenLoaded('assignedPlan', function() {
                    return [
                        'id' => $this->assignedPlan->id,
                        'name' => $this->assignedPlan->name,
                        'slug' => $this->assignedPlan->slug,
                        'products' => $this->assignedPlan->products->map(fn($p) => $p->only(['id', 'name', 'slug'])),
                    ];
                }),
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
                    $users = $this->users ?? collect();
                    $relationships['users'] = [
                        'data' => $users->map(fn($user) => [
                            'type' => 'users',
                            'id' => $user->external_id,
                        ]),
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

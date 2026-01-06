<?php

namespace Amrshah\TenantEngine\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'plans',
            'attributes' => [
                'name' => $this->name,
                'slug' => $this->slug,
                'price' => (int) $this->price,
                'currency' => $this->currency,
                'interval' => $this->interval,
                'is_active' => (bool) $this->is_active,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'products' => [
                    'data' => ProductResource::collection($this->whenLoaded('products')),
                ],
            ],
            'links' => [
                'self' => route('super-admin.plans.show', $this->id),
            ],
        ];
    }
}

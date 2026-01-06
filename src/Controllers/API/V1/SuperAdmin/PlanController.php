<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends BaseController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $plans = Plan::with('products')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($plans, \Amrshah\TenantEngine\Http\Resources\PlanResource::class);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'price' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|in:monthly,yearly',
            'is_active' => 'boolean',
            'products' => 'array',
            'products.*' => 'exists:products,id',
        ]);

        $plan = Plan::create(collect($validated)->except('products')->toArray());

        if (isset($validated['products'])) {
            $plan->products()->sync($validated['products']);
        }

        return $this->createdResponse(new \Amrshah\TenantEngine\Http\Resources\PlanResource($plan->load('products')));
    }

    public function show(Plan $plan): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(new \Amrshah\TenantEngine\Http\Resources\PlanResource($plan->load('products')));
    }

    public function update(Request $request, Plan $plan): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('plans')->ignore($plan->id)],
            'price' => 'sometimes|integer|min:0',
            'currency' => 'sometimes|string|size:3',
            'interval' => 'sometimes|in:monthly,yearly',
            'is_active' => 'boolean',
            'products' => 'array',
            'products.*' => 'exists:products,id',
        ]);

        $plan->update(collect($validated)->except('products')->toArray());

        if (isset($validated['products'])) {
            $plan->products()->sync($validated['products']);
        }

        return $this->successResponse(new \Amrshah\TenantEngine\Http\Resources\PlanResource($plan->load('products')));
    }

    public function destroy(Plan $plan): \Illuminate\Http\JsonResponse
    {
        $plan->delete();

        return $this->noContentResponse();
    }
}

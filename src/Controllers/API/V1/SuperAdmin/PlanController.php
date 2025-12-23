<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends BaseController
{
    public function index(Request $request)
    {
        $plans = Plan::with('products')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($plans);
    }

    public function store(Request $request)
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

        return response()->json($plan->load('products'), 201);
    }

    public function show(Plan $plan)
    {
        return response()->json($plan->load('products'));
    }

    public function update(Request $request, Plan $plan)
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

        return response()->json($plan->load('products'));
    }

    public function destroy(Plan $plan)
    {
        // Check if plan has tenants? Maybe prevent deletion or nullify.
        // For now, allow delete (constraints might fail if migration didn't handle cascade properly on tenants table? I set nullable on tenants, so it's fine)
        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully']);
    }
}

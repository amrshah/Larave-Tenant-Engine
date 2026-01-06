<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends BaseController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $products = Product::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($products, \Amrshah\TenantEngine\Http\Resources\ProductResource::class);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return $this->createdResponse(new \Amrshah\TenantEngine\Http\Resources\ProductResource($product));
    }

    public function show(Product $product): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(new \Amrshah\TenantEngine\Http\Resources\ProductResource($product));
    }

    public function update(Request $request, Product $product): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return $this->successResponse(new \Amrshah\TenantEngine\Http\Resources\ProductResource($product));
    }

    public function destroy(Product $product): \Illuminate\Http\JsonResponse
    {
        $product->delete();

        return $this->noContentResponse();
    }
}

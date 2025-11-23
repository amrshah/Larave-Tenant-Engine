<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Http\Requests\CreateTenantRequest;
use Amrshah\TenantEngine\Http\Requests\UpdateTenantRequest;
use Amrshah\TenantEngine\Http\Resources\TenantResource;
use Amrshah\TenantEngine\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Super Admin - Tenants",
 *     description="Tenant management for super admins"
 * )
 */
class TenantController extends BaseController
{
    /**
     * Display a listing of tenants.
     * 
     * @OA\Get(
     *     path="/api/v1/super-admin/tenants",
     *     summary="List all tenants",
     *     tags={"Super Admin - Tenants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "suspended", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="filter[plan]",
     *         in="query",
     *         description="Filter by plan",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of tenants")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();

        // Filtering
        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        if ($request->has('filter.plan')) {
            $query->where('plan', $request->input('filter.plan'));
        }

        if ($request->has('filter.search')) {
            $search = $request->input('filter.search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort', '-created_at');
        $sortDirection = str_starts_with($sortBy, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortBy, '-');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min($request->input('page.size', 15), 100);
        $tenants = $query->paginate($perPage);

        return $this->paginatedResponse($tenants, TenantResource::class);
    }

    /**
     * Store a newly created tenant.
     * 
     * @OA\Post(
     *     path="/api/v1/super-admin/tenants",
     *     summary="Create a new tenant",
     *     tags={"Super Admin - Tenants"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "slug"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="plan", type="string"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tenant created")
     * )
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $tenant = Tenant::create([
                'id' => $request->slug,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'plan' => $request->plan ?? config('tenant-engine.tenant.default_plan'),
                'status' => config('tenant-engine.tenant.default_status'),
                'trial_ends_at' => $request->trial_days ? now()->addDays($request->trial_days) : null,
            ]);

            // Tenant database will be created automatically by Stancl

            DB::commit();

            return $this->createdResponse([
                'type' => 'tenants',
                'id' => $tenant->external_id,
                'attributes' => [
                    'name' => $tenant->name,
                    'slug' => $tenant->id,
                    'email' => $tenant->email,
                    'phone' => $tenant->phone,
                    'plan' => $tenant->plan,
                    'status' => $tenant->status,
                    'created_at' => $tenant->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password']),
                'user_id' => auth()->id(),
            ]);
            
            return $this->errorResponse('Tenant Creation Failed', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified tenant.
     * 
     * @OA\Get(
     *     path="/api/v1/super-admin/tenants/{tenant}",
     *     summary="Get tenant details",
     *     tags={"Super Admin - Tenants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Tenant details")
     * )
     */
    public function show(string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'name' => $tenant->name,
                'slug' => $tenant->id,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
                'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'created_at' => $tenant->created_at->toIso8601String(),
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
            'meta' => [
                'users_count' => $tenant->users()->count(),
                'is_on_trial' => $tenant->isOnTrial(),
                'has_active_subscription' => $tenant->hasActiveSubscription(),
            ],
        ]);
    }

    /**
     * Update the specified tenant.
     * 
     * @OA\Patch(
     *     path="/api/v1/super-admin/tenants/{tenant}",
     *     summary="Update tenant",
     *     tags={"Super Admin - Tenants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Tenant updated")
     * )
     */
    public function update(UpdateTenantRequest $request, string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);

        $tenant->update($request->only(['name', 'email', 'phone', 'plan']));

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'plan' => $tenant->plan,
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Remove the specified tenant.
     * 
     * @OA\Delete(
     *     path="/api/v1/super-admin/tenants/{tenant}",
     *     summary="Delete tenant",
     *     tags={"Super Admin - Tenants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tenant",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=204, description="Tenant deleted")
     * )
     */
    public function destroy(string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);
        $tenant->delete();

        return $this->noContentResponse();
    }

    /**
     * Suspend the specified tenant.
     */
    public function suspend(string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);
        $tenant->suspend();

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'status' => $tenant->status,
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Activate the specified tenant.
     */
    public function activate(string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);
        $tenant->activate();

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'status' => $tenant->status,
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Cancel the specified tenant.
     */
    public function cancel(string $tenant): JsonResponse
    {
        $tenant = Tenant::findByExternalIdOrFail($tenant);
        $tenant->cancel();

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'status' => $tenant->status,
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }
}

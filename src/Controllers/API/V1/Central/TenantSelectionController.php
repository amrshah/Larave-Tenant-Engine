<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Central;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Http\Requests\CreateTenantRequest;
use Amrshah\TenantEngine\Http\Resources\TenantResource;
use Amrshah\TenantEngine\Models\Tenant;
use Amrshah\TenantEngine\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSelectionController extends BaseController
{
    public function __construct(
        protected TenantService $tenantService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // If super admin, they should see all tenants
        if ($user instanceof \Amrshah\TenantEngine\Models\SuperAdmin) {
            $tenants = \Amrshah\TenantEngine\Models\Tenant::all();
        } else {
            // Check if relationship exists, otherwise return empty collection
            $tenants = method_exists($user, 'tenants') ? $user->tenants : collect();
            
            // In case it's still null (relationship exists but returns null somehow)
            $tenants = $tenants ?? collect();
        }

        return $this->successResponse(
            \Amrshah\TenantEngine\Http\Resources\TenantResource::collection($tenants)
        );
    }

    public function show(Request $request, string $tenant): JsonResponse
    {
        $user = $request->user();
        $tenantModel = config('tenancy.tenant_model');
        
        $tenant = $tenantModel::findByExternalIdOrFail($tenant);

        // Check if user belongs to this tenant (unless super admin)
        if (!($user instanceof \Amrshah\TenantEngine\Models\SuperAdmin) && 
            !(method_exists($user, 'tenants') && $user->tenants->contains($tenant))) {
            return $this->forbiddenResponse('You do not have access to this tenant');
        }

        return $this->successResponse(new \Amrshah\TenantEngine\Http\Resources\TenantResource($tenant));
    }

    public function switch(Request $request, string $tenant): JsonResponse
    {
        $user = $request->user();
        $tenantModel = config('tenancy.tenant_model');
        
        $tenant = $tenantModel::findByExternalIdOrFail($tenant);

        // Check if user belongs to this tenant (unless super admin)
        if (!($user instanceof \Amrshah\TenantEngine\Models\SuperAdmin) && 
            !(method_exists($user, 'tenants') && $user->tenants->contains($tenant))) {
            return $this->forbiddenResponse('You do not have access to this tenant');
        }

        // Store current tenant in session or return tenant info
        return $this->successResponse([
            'type' => 'tenant-switch',
            'id' => 'current',
            'attributes' => [
                'message' => 'Switched to tenant successfully',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'external_id' => $tenant->external_id,
                    'slug' => $tenant->id,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created tenant and link to current user.
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Create the tenant
            $tenant = $this->tenantService->createTenant($request->validated());

            // Link user to tenant as owner
            if (method_exists($user, 'tenants')) {
                $user->tenants()->attach($tenant, ['role' => 'owner']);
            }

            return $this->createdResponse(new TenantResource($tenant));
        } catch (\Exception $e) {
            return $this->errorResponse('Tenant Creation Failed', $e->getMessage(), 500);
        }
    }
    /**
     * Delete a tenant.
     */
    public function destroy(Request $request, string $tenant): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantModel = config('tenancy.tenant_model');
            
            // Try to find by ID (slug) first, then external ID
            $tenant = $tenantModel::where('id', $tenant)
                ->orWhere('external_id', $tenant)
                ->firstOrFail();

            // Check if user belongs to this tenant and has 'owner' role (or is super admin)
            // For now, simpler check: must be attached to tenant.
            // TODO: Enforce OWNER role specifically if roles are implemented on pivot.
            if (!($user instanceof \Amrshah\TenantEngine\Models\SuperAdmin) && 
                !(method_exists($user, 'tenants') && $user->tenants->contains($tenant))) {
                return $this->forbiddenResponse('You do not have access to delete this tenant');
            }

            // Optional: Check if user is the owner (if pivot has role)
            // if ($user->tenants->find($tenant->id)->pivot->role !== 'owner') { ... }

            $tenant->delete();

            return $this->successResponse(['message' => 'Tenant deleted successfully']);
        } catch (\Exception $e) {
            return $this->errorResponse('Tenant Deletion Failed', $e->getMessage(), 500);
        }
    }
}

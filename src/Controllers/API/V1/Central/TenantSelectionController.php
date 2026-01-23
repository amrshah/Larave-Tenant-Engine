<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Central;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSelectionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenants = $user->tenants;

        return $this->successResponse([
            'type' => 'tenants',
            'data' => $tenants->map(fn($tenant) => [
                'type' => 'tenants',
                'id' => $tenant->external_id,
                'attributes' => [
                    'name' => $tenant->name,
                    'slug' => $tenant->id,
                    'plan' => $tenant->plan,
                    'status' => $tenant->status,
                ],
            ]),
        ]);
    }

    public function show(Request $request, string $tenant): JsonResponse
    {
        $user = $request->user();
        $tenantModel = config('tenancy.tenant_model');
        
        $tenant = $tenantModel::findByExternalIdOrFail($tenant);

        // Check if user belongs to this tenant
        if (!$user->tenants->contains($tenant)) {
            return $this->forbiddenResponse('You do not have access to this tenant');
        }

        return $this->successResponse([
            'type' => 'tenants',
            'id' => $tenant->external_id,
            'attributes' => [
                'name' => $tenant->name,
                'slug' => $tenant->id,
                'email' => $tenant->email,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
            ],
        ]);
    }

    public function switch(Request $request, string $tenant): JsonResponse
    {
        $user = $request->user();
        $tenantModel = config('tenancy.tenant_model');
        
        $tenant = $tenantModel::findByExternalIdOrFail($tenant);

        // Check if user belongs to this tenant
        if (!$user->tenants->contains($tenant)) {
            return $this->forbiddenResponse('You do not have access to this tenant');
        }

        // Store current tenant in session or return tenant info
        return $this->successResponse([
            'type' => 'tenant-switch',
            'attributes' => [
                'message' => 'Switched to tenant successfully',
                'tenant' => [
                    'id' => $tenant->external_id,
                    'name' => $tenant->name,
                    'slug' => $tenant->id,
                ],
            ],
        ]);
    }
}

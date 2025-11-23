<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Amrshah\Arbac\Facades\Arbac;

class RoleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        // Get roles using ARBAC
        $roles = Arbac::getRoles();

        return $this->successResponse([
            'type' => 'roles',
            'data' => $roles->map(fn($role) => [
                'type' => 'roles',
                'id' => $role->id,
                'attributes' => [
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? $role->name,
                    'description' => $role->description ?? null,
                    'permissions_count' => $role->permissions->count(),
                ],
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Create role using ARBAC
        $role = Arbac::createRole(
            $request->name,
            $request->display_name,
            $request->description
        );

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return $this->createdResponse([
            'type' => 'roles',
            'id' => $role->id,
            'attributes' => [
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
            ],
        ]);
    }

    public function show(string $role): JsonResponse
    {
        $role = Arbac::getRole($role);

        if (!$role) {
            return $this->notFoundResponse('Role');
        }

        return $this->successResponse([
            'type' => 'roles',
            'id' => $role->id,
            'attributes' => [
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function update(Request $request, string $role): JsonResponse
    {
        $role = Arbac::getRole($role);

        if (!$role) {
            return $this->notFoundResponse('Role');
        }

        $validator = Validator::make($request->all(), [
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $role->update($request->only(['display_name', 'description']));

        return $this->successResponse([
            'type' => 'roles',
            'id' => $role->id,
            'attributes' => [
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
            ],
        ]);
    }

    public function destroy(string $role): JsonResponse
    {
        $role = Arbac::getRole($role);

        if (!$role) {
            return $this->notFoundResponse('Role');
        }

        $role->delete();

        return $this->noContentResponse();
    }

    public function assignPermissions(Request $request, string $role): JsonResponse
    {
        $role = Arbac::getRole($role);

        if (!$role) {
            return $this->notFoundResponse('Role');
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Sync permissions
        $role->syncPermissions($request->permissions);

        return $this->successResponse([
            'type' => 'role-permissions',
            'attributes' => [
                'message' => 'Permissions assigned successfully',
                'permissions' => $request->permissions,
            ],
        ]);
    }
}

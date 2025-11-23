<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Amrshah\Arbac\Facades\Arbac;

class PermissionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $permissions = Arbac::getPermissions();

        return $this->successResponse([
            'type' => 'permissions',
            'data' => $permissions->map(fn($permission) => [
                'type' => 'permissions',
                'id' => $permission->id,
                'attributes' => [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? $permission->name,
                    'description' => $permission->description ?? null,
                    'group' => $permission->group ?? 'general',
                ],
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'group' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $permission = Arbac::createPermission(
            $request->name,
            $request->display_name,
            $request->description,
            $request->group
        );

        return $this->createdResponse([
            'type' => 'permissions',
            'id' => $permission->id,
            'attributes' => [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'group' => $permission->group,
            ],
        ]);
    }

    public function show(string $permission): JsonResponse
    {
        $permission = Arbac::getPermission($permission);

        if (!$permission) {
            return $this->notFoundResponse('Permission');
        }

        return $this->successResponse([
            'type' => 'permissions',
            'id' => $permission->id,
            'attributes' => [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'group' => $permission->group,
                'roles' => $permission->roles->pluck('name'),
            ],
        ]);
    }

    public function update(Request $request, string $permission): JsonResponse
    {
        $permission = Arbac::getPermission($permission);

        if (!$permission) {
            return $this->notFoundResponse('Permission');
        }

        $validator = Validator::make($request->all(), [
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'group' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $permission->update($request->only(['display_name', 'description', 'group']));

        return $this->successResponse([
            'type' => 'permissions',
            'id' => $permission->id,
            'attributes' => [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'group' => $permission->group,
            ],
        ]);
    }

    public function destroy(string $permission): JsonResponse
    {
        $permission = Arbac::getPermission($permission);

        if (!$permission) {
            return $this->notFoundResponse('Permission');
        }

        $permission->delete();

        return $this->noContentResponse();
    }
}

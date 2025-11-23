<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\SuperAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SuperAdmin::query();

        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        if ($request->has('filter.search')) {
            $search = $request->input('filter.search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sort', '-created_at');
        $sortDirection = str_starts_with($sortBy, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortBy, '-');
        $query->orderBy($sortField, $sortDirection);

        $perPage = min($request->input('page.size', 15), 100);
        $admins = $query->paginate($perPage);

        return $this->paginatedResponse($admins, SuperAdminResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:super_admins,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $admin = SuperAdmin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        return $this->createdResponse([
            'type' => 'super-admins',
            'id' => $admin->external_id,
            'attributes' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => $admin->status,
                'created_at' => $admin->created_at->toIso8601String(),
            ],
        ]);
    }

    public function show(string $admin): JsonResponse
    {
        $admin = SuperAdmin::findByExternalIdOrFail($admin);

        return $this->successResponse([
            'type' => 'super-admins',
            'id' => $admin->external_id,
            'attributes' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'status' => $admin->status,
                'last_login_at' => $admin->last_login_at?->toIso8601String(),
                'created_at' => $admin->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, string $admin): JsonResponse
    {
        $admin = SuperAdmin::findByExternalIdOrFail($admin);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:super_admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $data = $request->only(['name', 'email', 'phone']);
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return $this->successResponse([
            'type' => 'super-admins',
            'id' => $admin->external_id,
            'attributes' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'updated_at' => $admin->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(string $admin): JsonResponse
    {
        $admin = SuperAdmin::findByExternalIdOrFail($admin);
        $admin->delete();

        return $this->noContentResponse();
    }

    public function suspend(string $admin): JsonResponse
    {
        $admin = SuperAdmin::findByExternalIdOrFail($admin);
        $admin->suspend();

        return $this->successResponse([
            'type' => 'super-admins',
            'id' => $admin->external_id,
            'attributes' => [
                'status' => $admin->status,
            ],
        ]);
    }

    public function activate(string $admin): JsonResponse
    {
        $admin = SuperAdmin::findByExternalIdOrFail($admin);
        $admin->activate();

        return $this->successResponse([
            'type' => 'super-admins',
            'id' => $admin->external_id,
            'attributes' => [
                'status' => $admin->status,
            ],
        ]);
    }
}

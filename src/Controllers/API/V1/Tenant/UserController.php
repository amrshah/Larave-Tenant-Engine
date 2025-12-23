<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Amrshah\TenantEngine\Http\Resources\UserResource;

class UserController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->tenant();
        $query = $tenant->users();

        // Filtering
        if ($request->has('filter.role')) {
            $query->wherePivot('role', $request->input('filter.role'));
        }

        if ($request->has('filter.search')) {
            $search = $request->input('filter.search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort', '-created_at');
        $sortDirection = str_starts_with($sortBy, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortBy, '-');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min($request->input('page.size', 15), 100);
        $users = $query->paginate($perPage);

        return $this->paginatedResponse($users, UserResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'nullable|string|in:owner,admin,member,viewer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        
        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password ?? \Illuminate\Support\Str::random(16)),
        ]);

        // Attach user to current tenant
        $tenant = $this->tenant();
        $tenant->users()->attach($user->id, [
            'role' => $request->role ?? 'tenant_member',
        ]);

        return $this->createdResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $request->role ?? 'tenant_member',
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }

    public function show(string $user): JsonResponse
    {
        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::findByExternalIdOrFail($user);

        // Check if user belongs to current tenant
        $tenant = $this->tenant();
        if (!$tenant->users->contains($user)) {
            return $this->forbiddenResponse('User does not belong to this tenant');
        }

        $role = $tenant->users()->where('user_id', $user->id)->first()->pivot->role;

        return $this->successResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, string $user): JsonResponse
    {
        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::findByExternalIdOrFail($user);

        $tenant = $this->tenant();
        if (!$tenant->users->contains($user)) {
            return $this->forbiddenResponse('User does not belong to this tenant');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|string|in:owner,admin,member,viewer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user->update($request->only(['name', 'email']));

        // Update role if provided
        if ($request->has('role')) {
            $tenant->users()->updateExistingPivot($user->id, [
                'role' => $request->role,
            ]);
        }

        return $this->successResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $tenant->users()->where('user_id', $user->id)->first()->pivot->role,
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(string $user): JsonResponse
    {
        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::findByExternalIdOrFail($user);

        $tenant = $this->tenant();
        
        // Detach user from tenant
        $tenant->users()->detach($user->id);

        return $this->noContentResponse();
    }

    public function invite(Request $request, string $user): JsonResponse
    {
        // TODO: Implement user invitation logic
        
        return $this->successResponse([
            'type' => 'user-invitation',
            'attributes' => [
                'message' => 'Invitation sent successfully',
            ],
        ]);
    }

    public function resendInvitation(Request $request, string $user): JsonResponse
    {
        // TODO: Implement resend invitation logic
        
        return $this->successResponse([
            'type' => 'user-invitation',
            'attributes' => [
                'message' => 'Invitation resent successfully',
            ],
        ]);
    }
}

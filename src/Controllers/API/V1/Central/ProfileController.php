<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Central;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends BaseController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user->update($request->only(['name', 'email', 'phone']));

        return $this->successResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Invalid Password', 'Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse([
            'type' => 'password-change',
            'attributes' => [
                'message' => 'Password changed successfully',
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke all tokens
        $user->tokens()->delete();
        
        // Soft delete user
        $user->delete();

        return $this->noContentResponse();
    }
}

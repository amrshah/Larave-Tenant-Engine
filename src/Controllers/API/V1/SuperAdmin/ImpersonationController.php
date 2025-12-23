<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpersonationController extends BaseController
{
    public function start(Request $request, string $user): JsonResponse
    {
        if (!config('tenant-engine.super_admin.impersonation.enabled', true)) {
            return $this->forbiddenResponse('Impersonation is disabled');
        }

        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $targetUser = $userModel::findByExternalIdOrFail($user);

        // Store impersonation info in session
        $sessionKey = config('tenant-engine.super_admin.impersonation.session_key', 'impersonated_by');
        session([$sessionKey => $request->user()->id]);

        // Create token for target user
        $token = $targetUser->createToken('impersonation-token')->plainTextToken;

        return $this->successResponse([
            'type' => 'impersonation',
            'attributes' => [
                'message' => 'Impersonation started successfully',
                'impersonated_user' => [
                    'id' => $targetUser->external_id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $sessionKey = config('tenant-engine.super_admin.impersonation.session_key', 'impersonated_by');
        
        if (!session()->has($sessionKey)) {
            return $this->errorResponse('Not Impersonating', 'No active impersonation session', 400);
        }

        $superAdminId = session($sessionKey);
        session()->forget($sessionKey);

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse([
            'type' => 'impersonation',
            'attributes' => [
                'message' => 'Impersonation stopped successfully',
            ],
        ]);
    }
}

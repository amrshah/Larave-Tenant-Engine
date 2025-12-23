<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Auth;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 */
class AuthController extends BaseController
{
    /**
     * Register a new user.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8)
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        if ($request->has('data.attributes')) {
            $request->merge($request->input('data.attributes'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
            
            $user = $userModel::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->createdResponse([
                'type' => 'auth',
                'id' => 'current',
                'attributes' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => null,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $user->external_id,
                        ],
                    ],
                ],
            ], [
                (new \Amrshah\TenantEngine\Http\Resources\UserResource($user))->resolve($request)
            ]);
        } catch (\Exception $e) {
            \Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);
            
            return $this->errorResponse(
                'Registration Failed',
                'Unable to create user account. Please try again.',
                500
            );
        }
    }

    /**
     * Login user.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        if ($request->has('data.attributes')) {
            $request->merge($request->input('data.attributes'));
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'type' => 'auth',
            'id' => 'current',
            'attributes' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => null,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $user->external_id,
                    ],
                ],
            ],
        ], 200, [], [], [
            (new \Amrshah\TenantEngine\Http\Resources\UserResource($user))->resolve($request)
        ]);
    }

    /**
     * Login super admin.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/super-admin/login",
     *     summary="Login super admin",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function superAdminLogin(Request $request): JsonResponse
    {
        if ($request->has('data.attributes')) {
            $request->merge($request->input('data.attributes'));
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $superAdmin = \Amrshah\TenantEngine\Models\SuperAdmin::where('email', $request->email)->first();

        if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
            return $this->unauthorizedResponse('Invalid credentials');
        }

        // Create token
        $token = $superAdmin->createToken('super-admin-token')->plainTextToken;

        return $this->successResponse([
            'type' => 'auth',
            'id' => 'current',
            'attributes' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => null,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users', // Use 'users' type for uniform auth logic
                        'id' => $superAdmin->external_id,
                    ],
                ],
            ],
        ], 200, [], [], [
            array_merge(
                (new \Amrshah\TenantEngine\Http\Resources\SuperAdminResource($superAdmin))->resolve($request),
                ['type' => 'users'] // Override type to 'users' for uniform auth logic on frontend
            )
        ]);
    }

    /**
     * Logout user.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=204, description="Logout successful")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContentResponse();
    }

    /**
     * Get authenticated user.
     * 
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get current user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Current user details")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $resource = $user instanceof \Amrshah\TenantEngine\Models\SuperAdmin 
            ? new \Amrshah\TenantEngine\Http\Resources\SuperAdminResource($user)
            : new \Amrshah\TenantEngine\Http\Resources\UserResource($user);

        return $this->successResponse($resource);
    }

    /**
     * Refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        $resource = $user instanceof \Amrshah\TenantEngine\Models\SuperAdmin 
            ? new \Amrshah\TenantEngine\Http\Resources\SuperAdminResource($user)
            : new \Amrshah\TenantEngine\Http\Resources\UserResource($user);

        return $this->successResponse([
            'type' => 'auth',
            'id' => 'current',
            'attributes' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => null,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => $user->external_id,
                    ],
                ],
            ],
        ], 200, [], [], [
            array_merge(
                $resource->resolve($request),
                ['type' => 'users']
            )
        ]);
    }

    /**
     * Forgot password.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // TODO: Implement password reset email logic

        return $this->successResponse([
            'type' => 'password-reset',
            'attributes' => [
                'message' => 'Password reset link sent to your email',
            ],
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // TODO: Implement password reset logic

        return $this->successResponse([
            'type' => 'password-reset',
            'attributes' => [
                'message' => 'Password reset successfully',
            ],
        ]);
    }

    /**
     * Verify email.
     */
    public function verifyEmail(Request $request, $id, $hash): JsonResponse
    {
        $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->email))) {
            return $this->forbiddenResponse('Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse([
                'type' => 'email-verification',
                'attributes' => [
                    'message' => 'Email already verified',
                ],
            ]);
        }

        $user->markEmailAsVerified();

        return $this->successResponse([
            'type' => 'email-verification',
            'attributes' => [
                'message' => 'Email verified successfully',
            ],
        ]);
    }
}

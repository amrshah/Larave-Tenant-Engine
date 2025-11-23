<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Auth;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\OAuthProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends BaseController
{
    public function redirect(string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return $this->successResponse([
            'type' => 'oauth-redirect',
            'attributes' => [
                'provider' => $provider,
                'url' => $url,
            ],
        ]);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return $this->errorResponse('OAuth Failed', $e->getMessage(), 400);
        }

        $userModel = config('tenant-engine.models.user');
        
        // Find or create user
        $user = $userModel::where('email', $socialiteUser->getEmail())->first();

        if (!$user) {
            $user = $userModel::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'email_verified_at' => now(),
            ]);
        }

        // Store OAuth provider info
        OAuthProvider::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_id' => $socialiteUser->getId(),
                'provider_token' => $socialiteUser->token,
                'provider_refresh_token' => $socialiteUser->refreshToken,
                'provider_data' => [
                    'name' => $socialiteUser->getName(),
                    'email' => $socialiteUser->getEmail(),
                    'avatar' => $socialiteUser->getAvatar(),
                ],
            ]
        );

        // Create auth token
        $token = $user->createToken('oauth-token')->plainTextToken;

        return $this->successResponse([
            'type' => 'users',
            'id' => $user->external_id,
            'attributes' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'meta' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'provider' => $provider,
            ],
        ]);
    }

    public function connect(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);
        
        // TODO: Implement OAuth connection for existing user
        
        return $this->successResponse([
            'type' => 'oauth-connection',
            'attributes' => [
                'message' => 'OAuth provider connected successfully',
                'provider' => $provider,
            ],
        ]);
    }

    public function disconnect(Request $request, string $provider): JsonResponse
    {
        $this->validateProvider($provider);
        
        $deleted = OAuthProvider::where('user_id', $request->user()->id)
            ->where('provider', $provider)
            ->delete();

        if (!$deleted) {
            return $this->notFoundResponse('OAuth connection');
        }

        return $this->noContentResponse();
    }

    protected function validateProvider(string $provider): void
    {
        $allowed = config('tenant-engine.oauth.providers', []);
        
        if (!in_array($provider, $allowed)) {
            abort(400, "Provider {$provider} is not supported");
        }
    }
}

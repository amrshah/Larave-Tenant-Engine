<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'code' => 'TENANT_NOT_FOUND',
                        'title' => 'Tenant Not Found',
                        'detail' => 'The requested tenant does not exist',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 404);
        }

        // Check if tenant is active
        if ($tenant->status !== 'active') {
            $message = match ($tenant->status) {
                'suspended' => 'This tenant has been suspended. Please contact support.',
                'cancelled' => 'This tenant has been cancelled.',
                default => 'This tenant is not available.',
            };

            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'TENANT_UNAVAILABLE',
                        'title' => 'Tenant Unavailable',
                        'detail' => $message,
                        'meta' => [
                            'tenant_status' => $tenant->status,
                        ],
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        // Check if trial has ended
        if ($tenant->trialHasEnded() && !$tenant->hasActiveSubscription()) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '402',
                        'code' => 'SUBSCRIPTION_REQUIRED',
                        'title' => 'Subscription Required',
                        'detail' => 'Your trial has ended. Please subscribe to continue.',
                        'meta' => [
                            'trial_ended_at' => $tenant->trial_ends_at?->toIso8601String(),
                        ],
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 402);
        }

        return $next($request);
    }
}

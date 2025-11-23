<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '401',
                        'code' => 'UNAUTHENTICATED',
                        'title' => 'Unauthenticated',
                        'detail' => 'Authentication required',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 401);
        }

        // Check if user is a super admin
        $superAdminModel = config('tenant-engine.models.super_admin');
        $isSuperAdmin = $request->user() instanceof $superAdminModel;

        if (!$isSuperAdmin) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'FORBIDDEN',
                        'title' => 'Forbidden',
                        'detail' => 'Super admin access required',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        // Check if super admin is active
        if ($request->user()->status !== 'active') {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'ACCOUNT_SUSPENDED',
                        'title' => 'Account Suspended',
                        'detail' => 'Your account has been suspended',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Amrshah\Arbac\Facades\Arbac;

class TenantAdminOnly
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

        // Check if user has tenant_admin role using ARBAC
        if (!Arbac::hasRole($request->user(), 'tenant_admin')) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'FORBIDDEN',
                        'title' => 'Forbidden',
                        'detail' => 'Tenant admin access required',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        return $next($request);
    }
}

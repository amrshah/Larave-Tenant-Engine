<?php

namespace Amrshah\TenantEngine\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Amrshah\TenantEngine\Models\SuperAdmin;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'errors' => [[
                    'status' => '401',
                    'title' => 'Unauthenticated',
                    'detail' => 'Authentication required',
                ]],
                'jsonapi' => ['version' => '1.1'],
            ], 401);
        }

        // Check if user has super admin role or is SuperAdmin instance
        $isSuperAdmin = false;
        
        if ($request->user() instanceof SuperAdmin) {
            $isSuperAdmin = true;
        } elseif (method_exists($request->user(), 'hasRole')) {
            $roleName = config('tenant-engine.super_admin.role_name', 'super_admin');
            $isSuperAdmin = $request->user()->hasRole($roleName);
        }

        if (!$isSuperAdmin) {
            return response()->json([
                'errors' => [[
                    'status' => '403',
                    'title' => 'Forbidden',
                    'detail' => 'Super admin access required',
                    'code' => 'SUPER_ADMIN_REQUIRED',
                ]],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        // Check if super admin is active
        if (!$request->user()->isActive()) {
            return response()->json([
                'errors' => [[
                    'status' => '403',
                    'title' => 'Forbidden',
                    'detail' => 'Super admin account is not active',
                    'code' => 'ACCOUNT_SUSPENDED',
                ]],
                'jsonapi' => ['version' => '1.1'],
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseController
{
    public function overview(): JsonResponse
    {
        $tenantModel = config('tenancy.tenant_model');
        $userModel = config('tenant-engine.models.user');

        $data = [
            'tenants' => [
                'total' => $tenantModel::count(),
                'active' => $tenantModel::where('status', 'active')->count(),
                'suspended' => $tenantModel::where('status', 'suspended')->count(),
                'cancelled' => $tenantModel::where('status', 'cancelled')->count(),
                'new_this_month' => $tenantModel::whereMonth('created_at', now()->month)->count(),
            ],
            'users' => [
                'total' => $userModel::count(),
                'new_this_month' => $userModel::whereMonth('created_at', now()->month)->count(),
            ],
            'growth' => [
                'tenants_growth' => $this->calculateGrowth($tenantModel, 'tenants'),
                'users_growth' => $this->calculateGrowth($userModel, 'users'),
            ],
        ];

        return $this->successResponse([
            'type' => 'analytics-overview',
            'attributes' => $data,
        ]);
    }

    public function tenants(): JsonResponse
    {
        $tenantModel = config('tenancy.tenant_model');

        $byPlan = $tenantModel::select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->get()
            ->pluck('count', 'plan');

        $byStatus = $tenantModel::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $recentTenants = $tenantModel::latest()->take(10)->get();

        return $this->successResponse([
            'type' => 'tenant-analytics',
            'attributes' => [
                'by_plan' => $byPlan,
                'by_status' => $byStatus,
                'recent_tenants' => $recentTenants->map(fn($t) => [
                    'id' => $t->external_id,
                    'name' => $t->name,
                    'plan' => $t->plan,
                    'created_at' => $t->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        $userModel = config('tenant-engine.models.user');

        $totalUsers = $userModel::count();
        $activeToday = $userModel::whereDate('last_login_at', today())->count();
        $newThisWeek = $userModel::where('created_at', '>=', now()->subWeek())->count();

        return $this->successResponse([
            'type' => 'user-analytics',
            'attributes' => [
                'total' => $totalUsers,
                'active_today' => $activeToday,
                'new_this_week' => $newThisWeek,
            ],
        ]);
    }

    public function revenue(): JsonResponse
    {
        // Placeholder for revenue analytics
        return $this->successResponse([
            'type' => 'revenue-analytics',
            'attributes' => [
                'total' => 0,
                'this_month' => 0,
                'last_month' => 0,
                'growth' => 0,
            ],
        ]);
    }

    protected function calculateGrowth($model, string $type): float
    {
        $thisMonth = $model::whereMonth('created_at', now()->month)->count();
        $lastMonth = $model::whereMonth('created_at', now()->subMonth()->month)->count();

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }
}

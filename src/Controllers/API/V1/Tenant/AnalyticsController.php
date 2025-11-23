<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends BaseController
{
    public function overview(): JsonResponse
    {
        $tenant = $this->tenant();
        
        $data = [
            'users' => [
                'total' => $tenant->users()->count(),
                'active_this_week' => $tenant->users()
                    ->where('last_login_at', '>=', now()->subWeek())
                    ->count(),
            ],
            'activity' => [
                'actions_today' => $this->getActionsToday(),
                'actions_this_week' => $this->getActionsThisWeek(),
            ],
        ];

        return $this->successResponse([
            'type' => 'tenant-analytics-overview',
            'attributes' => $data,
        ]);
    }

    public function users(): JsonResponse
    {
        $tenant = $this->tenant();

        $byRole = $tenant->users()
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role');

        return $this->successResponse([
            'type' => 'user-analytics',
            'attributes' => [
                'by_role' => $byRole,
                'total' => $tenant->users()->count(),
            ],
        ]);
    }

    public function activity(): JsonResponse
    {
        $tenant = $this->tenant();

        // Get activity from audit logs
        $auditLogModel = config('tenant-engine.models.audit_log');
        
        $recentActivity = $auditLogModel::currentTenant()
            ->latest()
            ->take(20)
            ->get();

        return $this->successResponse([
            'type' => 'activity-analytics',
            'data' => $recentActivity->map(fn($log) => [
                'type' => 'audit-logs',
                'id' => $log->external_id,
                'attributes' => [
                    'action' => $log->action,
                    'resource_type' => $log->resource_type,
                    'description' => $log->description,
                    'created_at' => $log->created_at->toIso8601String(),
                ],
            ]),
        ]);
    }

    protected function getActionsToday(): int
    {
        $auditLogModel = config('tenant-engine.models.audit_log');
        
        return $auditLogModel::currentTenant()
            ->whereDate('created_at', today())
            ->count();
    }

    protected function getActionsThisWeek(): int
    {
        $auditLogModel = config('tenant-engine.models.audit_log');
        
        return $auditLogModel::currentTenant()
            ->where('created_at', '>=', now()->subWeek())
            ->count();
    }
}

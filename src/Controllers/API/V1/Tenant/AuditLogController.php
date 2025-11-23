<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Amrshah\TenantEngine\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::currentTenant();

        // Filtering
        if ($request->has('filter.user_id')) {
            $query->where('user_id', $request->input('filter.user_id'));
        }

        if ($request->has('filter.action')) {
            $query->where('action', $request->input('filter.action'));
        }

        if ($request->has('filter.resource_type')) {
            $query->where('resource_type', $request->input('filter.resource_type'));
        }

        if ($request->has('filter.date_from')) {
            $query->where('created_at', '>=', $request->input('filter.date_from'));
        }

        if ($request->has('filter.date_to')) {
            $query->where('created_at', '<=', $request->input('filter.date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort', '-created_at');
        $sortDirection = str_starts_with($sortBy, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortBy, '-');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min($request->input('page.size', 15), 100);
        $logs = $query->paginate($perPage);

        return $this->paginatedResponse($logs, AuditLogResource::class);
    }

    public function show(string $log): JsonResponse
    {
        $log = AuditLog::currentTenant()->findByExternalIdOrFail($log);

        return $this->successResponse([
            'type' => 'audit-logs',
            'id' => $log->external_id,
            'attributes' => [
                'action' => $log->action,
                'resource_type' => $log->resource_type,
                'resource_id' => $log->resource_id,
                'description' => $log->description,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'changes' => $log->getChanges(),
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->toIso8601String(),
            ],
        ]);
    }
}

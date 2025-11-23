<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Amrshah\TenantEngine\Models\AuditLog;

class LogActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log if audit logging is enabled
        if (!config('tenant-engine.audit.enabled', true)) {
            return $response;
        }

        // Only log authenticated requests
        if (!$request->user()) {
            return $response;
        }

        // Only log state-changing methods
        if (!in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE'])) {
            return $response;
        }

        // Only log successful responses
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        try {
            $this->logActivity($request, $response);
        } catch (\Exception $e) {
            // Don't fail the request if logging fails
            \Log::error('Failed to log activity: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Log the activity.
     */
    protected function logActivity(Request $request, Response $response): void
    {
        $action = $this->determineAction($request);
        $resourceType = $this->extractResourceType($request);

        AuditLog::create([
            'tenant_id' => tenant('id'),
            'user_type' => get_class($request->user()),
            'user_id' => $request->user()->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'description' => $this->generateDescription($action, $resourceType),
            'metadata' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Determine the action from the request.
     */
    protected function determineAction(Request $request): string
    {
        return match ($request->method()) {
            'POST' => 'create',
            'PATCH', 'PUT' => 'update',
            'DELETE' => 'delete',
            default => 'unknown',
        };
    }

    /**
     * Extract resource type from request path.
     */
    protected function extractResourceType(Request $request): ?string
    {
        $path = $request->path();
        $segments = explode('/', $path);

        // Try to find resource type from path segments
        // Example: /api/v1/users/123 -> users
        foreach ($segments as $segment) {
            if (in_array($segment, ['api', 'v1', 'super-admin'])) {
                continue;
            }
            
            // Skip tenant slug (if present)
            if (tenant() && $segment === tenant('id')) {
                continue;
            }

            // Skip IDs (external IDs or numeric)
            if (preg_match('/^[A-Z]{3}_[a-zA-Z0-9]+$/', $segment) || is_numeric($segment)) {
                continue;
            }

            return $segment;
        }

        return null;
    }

    /**
     * Generate a human-readable description.
     */
    protected function generateDescription(string $action, ?string $resourceType): string
    {
        $resource = $resourceType ? ucfirst(str_replace('-', ' ', $resourceType)) : 'Resource';
        
        return match ($action) {
            'create' => "Created {$resource}",
            'update' => "Updated {$resource}",
            'delete' => "Deleted {$resource}",
            default => "Performed action on {$resource}",
        };
    }
}

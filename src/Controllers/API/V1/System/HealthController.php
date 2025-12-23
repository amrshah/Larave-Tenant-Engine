<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\System;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthController extends BaseController
{
    /**
     * System health check
     * 
     * @OA\Get(
     *     path="/api/v1/health",
     *     summary="System health check",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="System is healthy"
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="System is unhealthy"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        // System is healthy if critical services are up
        // Redis is optional, so we ignore it for health status
        $isHealthy = $services['database']['status'] === 'up' 
            && $services['storage']['status'] === 'up'
            && in_array($services['queue']['status'], ['up', 'unknown']);

        $response = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
            'version' => config('tenant-engine.api.version', '1.0.0'),
            'environment' => app()->environment(),
        ];

        return response()->json($response, $isHealthy ? 200 : 503);
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            $driver = DB::connection()->getDriverName();
            $connections = [];

            // Get connection info based on driver
            if ($driver === 'mysql') {
                try {
                    $connections = [
                        'active' => DB::connection()->select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
                        'max' => DB::connection()->select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 0,
                    ];
                } catch (\Exception $e) {
                    // Ignore if can't get connection stats
                }
            }

            return [
                'status' => 'up',
                'response_time' => $responseTime . 'ms',
                'driver' => $driver,
                'connections' => $connections ?: null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connection
     */
    protected function checkRedis(): array
    {
        // Check if Redis is configured and available
        if (!class_exists(\Illuminate\Support\Facades\Redis::class)) {
            return [
                'status' => 'not_configured',
                'message' => 'Redis not configured',
            ];
        }

        try {
            Redis::ping();
            
            return [
                'status' => 'up',
                'message' => 'Redis is operational',
            ];
        } catch (\Throwable $e) {
            \Log::warning('Redis health check failed', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);
            
            return [
                'status' => 'not_available',
                'message' => 'Redis not available (optional service)',
            ];
        }
    }

    /**
     * Check storage
     */
    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $totalSpace = disk_total_space($disk->path(''));
            $freeSpace = disk_free_space($disk->path(''));
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = round(($usedSpace / $totalSpace) * 100, 2);

            return [
                'status' => 'up',
                'disk_usage' => $usagePercent . '%',
                'available' => $this->formatBytes($freeSpace),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue
     */
    protected function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            return [
                'status' => 'up',
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . $units[$i];
    }

    /**
     * Simple ping endpoint
     * 
     * @OA\Get(
     *     path="/api/v1/ping",
     *     summary="Simple ping endpoint",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="Pong response"
     *     )
     * )
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'timestamp' => now()->toIso8601String(),
            'server_time' => now()->timestamp,
        ]);
    }

    /**
     * Version information
     * 
     * @OA\Get(
     *     path="/api/v1/version",
     *     summary="API version information",
     *     tags={"System"},
     *     @OA\Response(
     *         response=200,
     *         description="Version information"
     *     )
     * )
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'version',
                'attributes' => [
                    'api_version' => config('tenant-engine.api.version', '1.0.0'),
                    'api_specification' => 'JSON:API 1.1',
                    'laravel_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                    'package_version' => '1.0.0-alpha',
                ],
            ],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ]);
    }

    /**
     * System status (authenticated)
     * 
     * @OA\Get(
     *     path="/api/v1/status",
     *     summary="Detailed system status",
     *     tags={"System"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="System status"
     *     )
     * )
     */
    public function status(): JsonResponse
    {
        $metrics = [
            'total_tenants' => 'N/A',
            'active_tenants' => 'N/A',
            'total_users' => 'N/A',
        ];

        // Try to get tenant metrics
        try {
            $tenantModel = config('tenancy.tenant_model');
            if ($tenantModel && class_exists($tenantModel)) {
                $metrics['total_tenants'] = $tenantModel::count();
                $metrics['active_tenants'] = $tenantModel::where('status', 'active')->count();
            }
        } catch (\Exception $e) {
            // Ignore - metrics will remain N/A
        }

        // Try to get user metrics
        try {
            $userModel = config('tenant-engine.models.user') ?: config('auth.providers.users.model');
            if ($userModel && class_exists($userModel)) {
                $metrics['total_users'] = $userModel::count();
            }
        } catch (\Exception $e) {
            // Ignore - metrics will remain N/A
        }

        return $this->successResponse([
            'type' => 'system-status',
            'attributes' => [
                'status' => 'operational',
                'environment' => app()->environment(),
                'metrics' => $metrics,
            ],
        ]);
    }
}

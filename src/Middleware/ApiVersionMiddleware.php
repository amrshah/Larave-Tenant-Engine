<?php

namespace Amrshah\TenantEngine\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract version from route prefix
        $version = $this->extractVersion($request);
        
        // Get latest version from config
        $latestVersion = config('tenant-engine.api.latest_version', 'v1');
        
        // Check if version is deprecated
        $deprecatedVersions = config('tenant-engine.api.deprecated_versions', []);
        $isDeprecated = in_array($version, array_keys($deprecatedVersions));
        
        // Process request
        $response = $next($request);
        
        // Add version headers
        $headers = [
            'X-API-Version' => $version,
            'X-API-Latest-Version' => $latestVersion,
            'X-API-Deprecated' => $isDeprecated ? 'true' : 'false',
        ];
        
        // Add deprecation information if applicable
        if ($isDeprecated && isset($deprecatedVersions[$version])) {
            $deprecationInfo = $deprecatedVersions[$version];
            
            $headers['X-API-Sunset-Date'] = $deprecationInfo['sunset_date'] ?? '';
            
            if (isset($deprecationInfo['migration_guide'])) {
                $headers['Link'] = "<{$deprecationInfo['migration_guide']}>; rel=\"deprecation\"";
            }
            
            // Log deprecation warning
            \Log::warning("Deprecated API version accessed", [
                'version' => $version,
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }
        
        return $response->withHeaders($headers);
    }
    
    /**
     * Extract API version from request.
     */
    protected function extractVersion(Request $request): string
    {
        // Try to get from route prefix
        $path = $request->path();
        
        if (preg_match('#api/(v\d+)/#', $path, $matches)) {
            return $matches[1];
        }
        
        // Try to get from header
        if ($request->hasHeader('X-API-Version')) {
            return $request->header('X-API-Version');
        }
        
        // Default to latest version
        return config('tenant-engine.api.latest_version', 'v1');
    }
}

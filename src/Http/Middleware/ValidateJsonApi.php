<?php

namespace Amrshah\TenantEngine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJsonApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate POST, PATCH, PUT requests
        if (!in_array($request->method(), ['POST', 'PATCH', 'PUT'])) {
            return $next($request);
        }

        // Check Content-Type header
        $contentType = $request->header('Content-Type');
        
        if ($contentType && !str_contains($contentType, 'application/vnd.api+json') && !str_contains($contentType, 'application/json')) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '415',
                        'code' => 'INVALID_CONTENT_TYPE',
                        'title' => 'Invalid Content-Type',
                        'detail' => 'Content-Type must be application/vnd.api+json or application/json',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 415);
        }

        // Check Accept header
        $accept = $request->header('Accept');
        
        if ($accept && !str_contains($accept, 'application/vnd.api+json') && !str_contains($accept, 'application/json') && !str_contains($accept, '*/*')) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '406',
                        'code' => 'INVALID_ACCEPT',
                        'title' => 'Invalid Accept Header',
                        'detail' => 'Accept header must include application/vnd.api+json or application/json',
                    ],
                ],
                'jsonapi' => ['version' => '1.1'],
            ], 406);
        }

        return $next($request);
    }
}

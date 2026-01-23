<?php

namespace Amrshah\TenantEngine\Controllers\API;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="TenantEngine API",
 *     description="Multi-Tenant SaaS API with Super Admin and Tenant Admin capabilities",
 *     @OA\Contact(
 *         email="api@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development"
 * )
 * 
 * @OA\Server(
 *     url="https://api.example.com",
 *     description="Production"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 * 
 * @OA\Tag(
 *     name="System",
 *     description="System health and information endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and OAuth"
 * )
 * 
 * @OA\Tag(
 *     name="Super Admin",
 *     description="Super admin management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Tenants",
 *     description="Tenant management"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="User management"
 * )
 * 
 * @OA\Tag(
 *     name="Roles & Permissions",
 *     description="Role and permission management"
 * )
 */
class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response (JSON:API format)
     */
    protected function successResponse($data, int $statusCode = 200, array $meta = [], array $links = []): JsonResponse
    {
        $response = [
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = array_merge([
                'timestamp' => now()->toIso8601String(),
            ], $meta);
        } else {
            $response['meta'] = [
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if (!empty($links)) {
            $response['links'] = $links;
        }

        $response['jsonapi'] = [
            'version' => '1.1',
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Error response (JSON:API format)
     */
    protected function errorResponse(string $title, string $detail, int $statusCode = 400, ?string $code = null, array $meta = []): JsonResponse
    {
        $error = [
            'status' => (string) $statusCode,
            'title' => $title,
            'detail' => $detail,
        ];

        if ($code) {
            $error['code'] = $code;
        }

        if (!empty($meta)) {
            $error['meta'] = $meta;
        }

        return response()->json([
            'errors' => [$error],
            'jsonapi' => [
                'version' => '1.1',
            ],
        ], $statusCode);
    }

    /**
     * Validation error response (JSON:API format)
     */
    protected function validationErrorResponse(array $errors, string $title = 'Validation Error'): JsonResponse
    {
        $formattedErrors = [];

        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $message) {
                $formattedErrors[] = [
                    'status' => '422',
                    'title' => $title,
                    'detail' => $message,
                    'source' => [
                        'pointer' => "/data/attributes/{$field}",
                    ],
                ];
            }
        }

        return response()->json([
            'errors' => $formattedErrors,
            'jsonapi' => [
                'version' => '1.1',
            ],
        ], 422);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return $this->errorResponse(
            title: 'Not Found',
            detail: "{$resource} not found",
            statusCode: 404,
            code: 'RESOURCE_NOT_FOUND'
        );
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse(
            title: 'Unauthorized',
            detail: $message,
            statusCode: 401,
            code: 'UNAUTHORIZED'
        );
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse(
            title: 'Forbidden',
            detail: $message,
            statusCode: 403,
            code: 'FORBIDDEN'
        );
    }

    /**
     * Created response
     */
    protected function createdResponse($data, array $meta = [], array $links = []): JsonResponse
    {
        return $this->successResponse($data, 201, $meta, $links);
    }

    /**
     * No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse($paginator, $resourceClass): JsonResponse
    {
        $data = $resourceClass::collection($paginator->items());

        $meta = [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];

        $links = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        return $this->successResponse($data, 200, $meta, $links);
    }

    /**
     * Get authenticated user
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Get current tenant
     */
    protected function tenant()
    {
        return tenant();
    }

    /**
     * Check if user is super admin
     */
    protected function isSuperAdmin(): bool
    {
        return $this->user()?->hasRole(config('tenant-engine.super_admin.role_name', 'super_admin'));
    }

    /**
     * Check if user is tenant admin
     */
    protected function isTenantAdmin(): bool
    {
        return $this->user()?->hasRole('tenant_admin');
    }
}

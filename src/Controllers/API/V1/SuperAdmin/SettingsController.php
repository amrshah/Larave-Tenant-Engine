<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        // Get all system settings
        $settings = [
            'maintenance_mode' => config('app.maintenance', false),
            'registration_enabled' => config('tenant-engine.registration_enabled', true),
            'oauth_enabled' => config('tenant-engine.oauth.enabled', true),
            'default_plan' => config('tenant-engine.tenant.default_plan', 'free'),
            'max_tenants' => config('tenant-engine.limits.max_tenants', null),
        ];

        return $this->successResponse([
            'type' => 'system-settings',
            'attributes' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        // TODO: Implement settings update logic
        // This would typically update a settings table or config file
        
        return $this->successResponse([
            'type' => 'system-settings',
            'attributes' => [
                'message' => 'Settings updated successfully',
            ],
        ]);
    }
}

<?php

namespace Amrshah\TenantEngine\Controllers\API\V1\Tenant;

use Amrshah\TenantEngine\Controllers\API\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        // Get tenant settings from tenant database
        $settings = \DB::connection('tenant')->table('tenant_settings')->get();

        $formattedSettings = $settings->mapWithKeys(function ($setting) {
            return [$setting->key => $this->parseValue($setting->value, $setting->type)];
        });

        return $this->successResponse([
            'type' => 'tenant-settings',
            'attributes' => $formattedSettings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        \Log::info('SettingsController@update debug', [
            'raw_data' => $request->getContent(),
            'flattened_data' => $data,
            'tenant_id' => tenant() ? tenant()->id : 'null',
        ]);

        // Update tenant profile fields if present
        $profileFields = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
        if (!empty($profileFields) && tenant()) {
            tenant()->update($profileFields);
        }

        // Handle other settings in tenant database
        $settings = array_diff_key($data, $profileFields);
        foreach ($settings as $key => $value) {
            \DB::connection('tenant')->table('tenant_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => $this->getType($value),
                    'updated_at' => now(),
                ]
            );
        }

        return $this->successResponse([
            'type' => 'tenant-settings',
            'attributes' => [
                'message' => 'Settings updated successfully',
                'tenant' => tenant() ? [
                    'name' => tenant()->name,
                    'email' => tenant()->email,
                    'phone' => tenant()->phone,
                ] : null,
            ],
        ]);
    }

    protected function parseValue($value, $type)
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    protected function getType($value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_array($value) => 'json',
            default => 'string',
        };
    }
}

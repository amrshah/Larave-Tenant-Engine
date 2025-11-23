<?php

namespace Amrshah\TenantEngine\Models\Traits;

use Hidehalo\Nanoid\Client;
use Illuminate\Database\Eloquent\Model;

trait HasExternalId
{
    /**
     * Boot the trait
     */
    protected static function bootHasExternalId(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->external_id)) {
                $model->external_id = static::generateExternalId();
            }
        });
    }

    /**
     * Generate external ID with prefix
     */
    protected static function generateExternalId(): string
    {
        $prefix = static::getExternalIdPrefix();
        $client = new Client();
        
        $maxRetries = config('tenant-engine.external_id.max_retries', 10);
        $length = config('tenant-engine.external_id.length', 14);
        $alphabet = config('tenant-engine.external_id.alphabet');

        for ($i = 0; $i < $maxRetries; $i++) {
            $nanoId = $client->generateId($length, Client::MODE_DYNAMIC, $alphabet);
            $externalId = $prefix . '_' . $nanoId;

            // Check uniqueness
            if (!static::where('external_id', $externalId)->exists()) {
                return $externalId;
            }
        }

        throw new \RuntimeException('Failed to generate unique external ID after ' . $maxRetries . ' attempts');
    }

    /**
     * Get the prefix for external ID
     */
    protected static function getExternalIdPrefix(): string
    {
        $modelName = class_basename(static::class);
        $configKey = strtolower($modelName);
        
        // Check if prefix is defined in config
        $prefix = config("tenant-engine.external_id_prefixes.{$configKey}");
        
        // Fallback to first 3 characters of model name
        return $prefix ?? strtoupper(substr($modelName, 0, 3));
    }

    /**
     * Find model by external ID
     */
    public static function findByExternalId(string $externalId): ?Model
    {
        return static::where('external_id', $externalId)->first();
    }

    /**
     * Find model by external ID or fail
     */
    public static function findByExternalIdOrFail(string $externalId): Model
    {
        return static::where('external_id', $externalId)->firstOrFail();
    }

    /**
     * Route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'external_id';
    }

    /**
     * Scope query by external ID
     */
    public function scopeByExternalId($query, string $externalId)
    {
        return $query->where('external_id', $externalId);
    }
}

<?php

namespace Amrshah\TenantEngine\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OptimizesQueries
{
    /**
     * Scope to eager load relationships based on request includes.
     */
    public function scopeWithRequestedIncludes(Builder $query, ?string $includes = null): Builder
    {
        if (!$includes) {
            return $query;
        }

        $requestedIncludes = explode(',', $includes);
        $allowedIncludes = $this->getAllowedIncludes();

        foreach ($requestedIncludes as $include) {
            $include = trim($include);
            
            if (in_array($include, $allowedIncludes)) {
                $query->with($include);
            }
        }

        return $query;
    }

    /**
     * Get allowed includes for this model.
     */
    protected function getAllowedIncludes(): array
    {
        return property_exists($this, 'allowedIncludes') 
            ? $this->allowedIncludes 
            : [];
    }

    /**
     * Scope to apply common optimizations.
     */
    public function scopeOptimized(Builder $query): Builder
    {
        // Select only necessary columns if specified
        if (property_exists($this, 'defaultSelect')) {
            $query->select($this->defaultSelect);
        }

        // Apply default eager loading
        if (property_exists($this, 'defaultWith')) {
            $query->with($this->defaultWith);
        }

        return $query;
    }

    /**
     * Scope to apply filters efficiently.
     */
    public function scopeFilterBy(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (method_exists($this, $method = 'filter' . studly_case($field))) {
                $this->$method($query, $value);
            } elseif ($this->isFillable($field)) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Scope to apply sorting efficiently.
     */
    public function scopeSortBy(Builder $query, ?string $sortBy = null, string $direction = 'asc'): Builder
    {
        if (!$sortBy) {
            return $query;
        }

        $allowedSorts = property_exists($this, 'allowedSorts') 
            ? $this->allowedSorts 
            : $this->getFillable();

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        }

        return $query;
    }
}

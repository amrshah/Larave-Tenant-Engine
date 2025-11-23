<?php

namespace Amrshah\TenantEngine\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class TenantEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            dirname(__DIR__, 2).'/config/tenant-engine.php',
            'tenant-engine'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            dirname(__DIR__, 2).'/config/tenant-engine.php' => config_path('tenant-engine.php'),
        ], 'tenant-engine-config');

        // Publish central migrations
        $this->publishes([
            __DIR__.'/../Database/Migrations/central' => database_path('migrations/central'),
        ], 'tenant-engine-migrations-central');

        // Publish tenant migrations
        $this->publishes([
            __DIR__.'/../Database/Migrations/tenant' => database_path('migrations/tenant'),
        ], 'tenant-engine-migrations-tenant');

        // Load migrations (for package development)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations/central');

        // Register routes
        $this->registerRoutes();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Amrshah\TenantEngine\Commands\InstallCommand::class,
                \Amrshah\TenantEngine\Commands\CreateSuperAdminCommand::class,
                \Amrshah\TenantEngine\Commands\TenantCreateCommand::class,
                \Amrshah\TenantEngine\Commands\TenantMigrateCommand::class,
                \Amrshah\TenantEngine\Commands\GenerateSwaggerCommand::class,
            ]);
        }

        // Register middleware
        $this->registerMiddleware();
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        if (!config('tenant-engine.enabled', true)) {
            return;
        }

        // Super Admin routes
        Route::middleware(['api', 'api.version'])
            ->prefix('api/v1/super-admin')
            ->group(__DIR__.'/../Routes/super-admin.php');

        // Central API routes (authentication, tenant selection)
        Route::middleware(['api', 'api.version'])
            ->prefix('api/v1')
            ->group(__DIR__.'/../Routes/api.php');

        // Tenant-scoped routes
        Route::middleware(['api', 'api.version'])
            ->prefix('{tenant_slug}/api/v1')
            ->group(__DIR__.'/../Routes/tenant.php');
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register middleware aliases
        $middlewareAliases = config('tenant-engine.middleware', []);

        foreach ($middlewareAliases as $alias => $class) {
            if (class_exists($class)) {
                $router->aliasMiddleware($alias, $class);
            }
        }
    }
}

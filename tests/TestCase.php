<?php

namespace Amrshah\TenantEngine\Tests;

use Amrshah\TenantEngine\Providers\TenantEngineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Stancl\Tenancy\TenancyServiceProvider;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants table for Stancl
        Schema::create('tenants', function ($table) {
            $table->string('id')->primary();
            $table->string('external_id', 20)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('plan')->default('free');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create domains table for Stancl
        Schema::create('domains', function ($table) {
            $table->increments('id');
            $table->string('domain', 255)->unique();
            $table->string('tenant_id');
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
        });

        // Run package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations/central');
    }

    protected function getPackageProviders($app): array
    {
        return [
            TenancyServiceProvider::class,
            TenantEngineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup Stancl Tenancy config
        $app['config']->set('tenancy.tenant_model', \Amrshah\TenantEngine\Models\Tenant::class);
        $app['config']->set('tenancy.database.prefix', 'tenant');
        
        // Setup tenant-engine config
        $app['config']->set('tenant-engine.enabled', true);
        $app['config']->set('tenant-engine.models.user', \Illuminate\Foundation\Auth\User::class);
        $app['config']->set('tenant-engine.external_id_prefixes.tenants', 'TNT');
        $app['config']->set('tenant-engine.external_id_prefixes.super_admins', 'SAD');
    }
}

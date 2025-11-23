<?php

namespace Amrshah\TenantEngine\Commands;

use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant-engine:tenant:migrate
                            {tenant? : The tenant slug to migrate}
                            {--all : Migrate all tenants}
                            {--seed : Seed the database after migration}';

    /**
     * The console command description.
     */
    protected $description = 'Run migrations for tenant database(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->migrateAllTenants();
        }

        $tenantSlug = $this->argument('tenant');

        if (!$tenantSlug) {
            $this->error('Please specify a tenant slug or use --all flag');
            return self::FAILURE;
        }

        return $this->migrateTenant($tenantSlug);
    }

    /**
     * Migrate all tenants.
     */
    protected function migrateAllTenants(): int
    {
        $tenantModel = config('tenancy.tenant_model');
        $tenants = $tenantModel::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $this->info("Migrating {$tenants->count()} tenant(s)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $this->runMigrationForTenant($tenant);
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to migrate tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ All tenants migrated successfully!');

        return self::SUCCESS;
    }

    /**
     * Migrate a specific tenant.
     */
    protected function migrateTenant(string $tenantSlug): int
    {
        $tenantModel = config('tenancy.tenant_model');
        $tenant = $tenantModel::find($tenantSlug);

        if (!$tenant) {
            $this->error("Tenant '{$tenantSlug}' not found.");
            return self::FAILURE;
        }

        $this->info("Migrating tenant: {$tenant->name} ({$tenant->id})");
        $this->newLine();

        try {
            $this->runMigrationForTenant($tenant);
            
            $this->newLine();
            $this->info('✅ Tenant migrated successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Run migration for a tenant.
     */
    protected function runMigrationForTenant($tenant): void
    {
        Tenancy::initialize($tenant);

        $this->call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--database' => 'tenant',
                '--class' => 'TenantDatabaseSeeder',
                '--force' => true,
            ]);
        }

        Tenancy::end();
    }
}

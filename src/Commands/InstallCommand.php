<?php

namespace Amrshah\TenantEngine\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant-engine:install
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install the TenantEngine package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing TenantEngine...');
        $this->newLine();

        // Publish configuration
        $this->publishConfiguration();

        // Publish migrations
        $this->publishMigrations();

        // Run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->runMigrations();
        }

        // Create super admin
        if ($this->confirm('Create a super admin now?', true)) {
            $this->call('tenant-engine:create-super-admin');
        }

        $this->newLine();
        $this->info('✅ TenantEngine installed successfully!');
        $this->newLine();
        
        $this->displayNextSteps();

        return self::SUCCESS;
    }

    /**
     * Publish configuration files.
     */
    protected function publishConfiguration(): void
    {
        $this->info('Publishing configuration...');
        
        $params = [
            '--provider' => 'Amrshah\TenantEngine\Providers\TenantEngineServiceProvider',
            '--tag' => 'tenant-engine-config',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->info('Publishing migrations...');
        
        // Publish central migrations
        $this->call('vendor:publish', [
            '--provider' => 'Amrshah\TenantEngine\Providers\TenantEngineServiceProvider',
            '--tag' => 'tenant-engine-migrations-central',
            '--force' => $this->option('force'),
        ]);

        // Publish tenant migrations
        $this->call('vendor:publish', [
            '--provider' => 'Amrshah\TenantEngine\Providers\TenantEngineServiceProvider',
            '--tag' => 'tenant-engine-migrations-tenant',
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Run migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('Running standard migrations...');
        $this->call('migrate', [
            '--force' => $this->option('force') || !$this->confirm('Run in production mode?', false),
        ]);

        $this->newLine();
        $this->info('Running central migrations...');
        $this->call('migrate', [
            '--path' => 'database/migrations/central',
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Display next steps.
     */
    protected function displayNextSteps(): void
    {
        $this->info('Next steps:');
        $this->line('1. Configure your .env file with OAuth credentials (optional)');
        $this->line('2. Create your first tenant: php artisan tenant-engine:tenant:create');
        $this->line('3. View API documentation: /api/documentation');
        $this->newLine();
        $this->info('For more information, visit: https://github.com/amrshah/tenant-engine');
    }
}

<?php

namespace Amrshah\TenantEngine\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant-engine:swagger:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate Swagger API documentation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating Swagger documentation...');
        $this->newLine();

        try {
            // Generate Swagger documentation
            $this->call('l5-swagger:generate');

            $this->newLine();
            $this->info('✅ Swagger documentation generated successfully!');
            $this->newLine();
            
            $docUrl = url('/api/documentation');
            $this->info("View documentation at: {$docUrl}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate Swagger documentation: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Make sure l5-swagger is installed: composer require darkaonline/l5-swagger');
            
            return self::FAILURE;
        }
    }
}

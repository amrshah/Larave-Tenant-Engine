<?php

namespace Amrshah\TenantEngine\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class TenantCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant-engine:tenant:create
                            {--name= : The name of the tenant}
                            {--email= : The email of the tenant}
                            {--slug= : The slug of the tenant}
                            {--plan=free : The plan of the tenant}
                            {--trial-days=14 : Trial period in days}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating Tenant...');
        $this->newLine();

        $name = $this->option('name') ?? $this->ask('Tenant Name');
        $email = $this->option('email') ?? $this->ask('Email');
        $slug = $this->option('slug') ?? $this->ask('Slug (URL-friendly identifier)');
        $plan = $this->option('plan');
        $trialDays = (int) $this->option('trial-days');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'slug' => $slug,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'slug' => 'required|string|min:3|max:50|unique:tenants,id|alpha_dash',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line('  - ' . $error);
            }
            return self::FAILURE;
        }

        // Create tenant
        try {
            $tenantModel = config('tenancy.tenant_model');
            
            $tenant = $tenantModel::create([
                'id' => $slug,
                'name' => $name,
                'email' => $email,
                'plan' => $plan,
                'status' => 'active',
                'trial_ends_at' => now()->addDays($trialDays),
            ]);

            $this->newLine();
            $this->info('✅ Tenant created successfully!');
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $tenant->external_id],
                    ['Name', $tenant->name],
                    ['Slug', $tenant->id],
                    ['Email', $tenant->email],
                    ['Plan', $tenant->plan],
                    ['Trial Ends', \Illuminate\Support\Carbon::parse($tenant->trial_ends_at)->format('Y-m-d')],
                ]
            );

            $this->newLine();
            $this->info('Tenant URL: ' . url("/{$tenant->id}/api/v1"));

            // Run tenant migrations
            if ($this->confirm('Run tenant migrations now?', true)) {
                $this->call('tenant-engine:tenant:migrate', [
                    'tenant' => $tenant->id,
                ]);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create tenant: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

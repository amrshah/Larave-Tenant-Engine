<?php

namespace Amrshah\TenantEngine\Commands;

use Amrshah\TenantEngine\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant-engine:create-super-admin
                            {--name= : The name of the super admin}
                            {--email= : The email of the super admin}
                            {--password= : The password of the super admin}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new super admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating Super Admin...');
        $this->newLine();

        $name = $this->option('name') ?? $this->ask('Name');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:super_admins,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line('  - ' . $error);
            }
            return self::FAILURE;
        }

        // Create super admin
        try {
            $admin = SuperAdmin::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $this->newLine();
            $this->info('✅ Super Admin created successfully!');
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $admin->external_id],
                    ['Name', $admin->name],
                    ['Email', $admin->email],
                    ['Status', $admin->status],
                ]
            );

            $this->newLine();
            $this->info('You can now login with these credentials.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create super admin: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

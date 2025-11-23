<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only add indexes if tables exist
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                try {
                    $table->index(['status', 'created_at'], 'idx_tenants_status_created');
                } catch (\Exception $e) {
                    // Index might already exist
                }
                
                try {
                    $table->index('email', 'idx_tenants_email');
                } catch (\Exception $e) {
                    // Index might already exist
                }
                
                try {
                    $table->index(['plan', 'status'], 'idx_tenants_plan_status');
                } catch (\Exception $e) {
                    // Index might already exist
                }
            });
        }

        if (Schema::hasTable('super_admins')) {
            Schema::table('super_admins', function (Blueprint $table) {
                try {
                    $table->index(['status', 'created_at'], 'idx_super_admins_status_created');
                } catch (\Exception $e) {
                    // Index might already exist
                }
                
                try {
                    $table->index('email', 'idx_super_admins_email');
                } catch (\Exception $e) {
                    // Index might already exist
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('idx_tenants_status_created');
            $table->dropIndex('idx_tenants_email');
            $table->dropIndex('idx_tenants_plan_status');
        });

        Schema::table('super_admins', function (Blueprint $table) {
            $table->dropIndex('idx_super_admins_status_created');
            $table->dropIndex('idx_super_admins_email');
        });
    }
};

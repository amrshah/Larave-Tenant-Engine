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
        Schema::table('tenants', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index(['status', 'created_at'], 'idx_tenants_status_created');
            $table->index('email', 'idx_tenants_email');
            $table->index(['plan', 'status'], 'idx_tenants_plan_status');
        });

        Schema::table('super_admins', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_super_admins_status_created');
            $table->index('email', 'idx_super_admins_email');
        });
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

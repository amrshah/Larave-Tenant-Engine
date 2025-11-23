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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 20)->unique();
            
            // Tenant (nullable for central actions)
            $table->string('tenant_id')->nullable();
            
            // User (polymorphic)
            $table->string('user_type')->nullable(); // SuperAdmin, User, etc.
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Action
            $table->string('action'); // create, update, delete, login, etc.
            
            // Resource
            $table->string('resource_type')->nullable(); // Tenant, User, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('resource_external_id', 20)->nullable();
            
            // Details
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            
            // Request Information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('external_id');
            $table->index('tenant_id');
            $table->index(['user_type', 'user_id']);
            $table->index('action');
            $table->index(['resource_type', 'resource_id']);
            $table->index('resource_external_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

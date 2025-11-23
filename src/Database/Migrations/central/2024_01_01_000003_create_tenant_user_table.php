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
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->string('tenant_id'); // Stancl uses string for tenant ID
            $table->unsignedBigInteger('user_id');
            
            // Role in this tenant
            $table->string('role')->default('member');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('user_id');
            $table->unique(['tenant_id', 'user_id']);
            
            // Foreign Keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};

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
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 20)->unique();
            
            // User Relationship
            $table->unsignedBigInteger('user_id');
            
            // Provider Information
            $table->string('provider'); // google, microsoft, linkedin, facebook
            $table->string('provider_id');
            
            // Token Information
            $table->text('provider_token')->nullable();
            $table->text('provider_refresh_token')->nullable();
            $table->timestamp('provider_expires_at')->nullable();
            
            // Provider Data
            $table->json('provider_data')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('external_id');
            $table->index('user_id');
            $table->index(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_providers');
    }
};

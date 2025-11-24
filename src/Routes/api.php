<?php

use Illuminate\Support\Facades\Route;
use Amrshah\TenantEngine\Controllers\API\V1\System\HealthController;

/*
|--------------------------------------------------------------------------
| Central API Routes
|--------------------------------------------------------------------------
|
| These routes are for central functionality: authentication, tenant
| selection, and user profile management. They are not tenant-scoped.
|
*/

// System Health & Information
Route::get('/health', [HealthController::class, 'index'])->name('health');
Route::get('/ping', [HealthController::class, 'ping'])->name('ping');
Route::get('/version', [HealthController::class, 'version'])->name('version');
//Route::get('/status', [HealthController::class, 'status'])->name('status');

//System Status (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/status', [HealthController::class, 'status'])->name('status');
});

// Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Public routes with rate limiting
    Route::post('/register', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'register'])
        ->middleware('throttle:10,1') // 10 attempts per minute
        ->name('register');
    Route::post('/login', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5 attempts per minute
        ->name('login');
    Route::post('/forgot-password', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1') // 3 attempts per minute
        ->name('forgot-password');
    Route::post('/reset-password', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1') // 5 attempts per minute
        ->name('reset-password');
    
    // Email verification
    Route::get('/verify-email/{id}/{hash}', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verify-email');
    
    // OAuth routes
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('/{provider}', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\OAuthController::class, 'redirect'])->name('redirect');
        Route::get('/{provider}/callback', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\OAuthController::class, 'callback'])->name('callback');
        
        // Authenticated OAuth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/{provider}/connect', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\OAuthController::class, 'connect'])->name('connect');
            Route::delete('/{provider}/disconnect', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\OAuthController::class, 'disconnect'])->name('disconnect');
        });
    });
    
    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'refresh'])->name('refresh');
        Route::get('/me', [\Amrshah\TenantEngine\Controllers\API\V1\Auth\AuthController::class, 'me'])->name('me');
    });
});

// Tenant Selection & Management (for authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Central\TenantSelectionController::class, 'index'])->name('index');
        Route::get('/{tenant}', [\Amrshah\TenantEngine\Controllers\API\V1\Central\TenantSelectionController::class, 'show'])->name('show');
        Route::post('/{tenant}/switch', [\Amrshah\TenantEngine\Controllers\API\V1\Central\TenantSelectionController::class, 'switch'])->name('switch');
    });
    
    // User Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Central\ProfileController::class, 'show'])->name('show');
        Route::patch('/', [\Amrshah\TenantEngine\Controllers\API\V1\Central\ProfileController::class, 'update'])->name('update');
        Route::post('/change-password', [\Amrshah\TenantEngine\Controllers\API\V1\Central\ProfileController::class, 'changePassword'])->name('change-password');
        Route::delete('/', [\Amrshah\TenantEngine\Controllers\API\V1\Central\ProfileController::class, 'destroy'])->name('destroy');
    });
});

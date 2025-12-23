<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for super admin functionality only. They require
| super admin authentication and permissions.
|
*/

Route::middleware(['auth:sanctum', 'super_admin'])->group(function () {
    
    // Tenant Management
    Route::prefix('tenants')->name('super-admin.tenants.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'store'])->name('store');
        Route::get('/{tenant}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'show'])->name('show');
        Route::patch('/{tenant}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'update'])->name('update');
        Route::delete('/{tenant}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'destroy'])->name('destroy');
        
        // Tenant Actions
        Route::post('/{tenant}/suspend', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'suspend'])->name('suspend');
        Route::post('/{tenant}/activate', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'activate'])->name('activate');
        Route::post('/{tenant}/cancel', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\TenantController::class, 'cancel'])->name('cancel');
    });
    
    // Super Admin Management
    Route::prefix('admins')->name('super-admin.admins.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'store'])->name('store');
        Route::get('/{admin}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'show'])->name('show');
        Route::patch('/{admin}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'update'])->name('update');
        Route::delete('/{admin}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'destroy'])->name('destroy');
        
        // Super Admin Actions
        Route::post('/{admin}/suspend', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'suspend'])->name('suspend');
        Route::post('/{admin}/activate', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SuperAdminController::class, 'activate'])->name('activate');
    });
    
    // System Analytics
    Route::prefix('analytics')->name('super-admin.analytics.')->group(function () {
        Route::get('/overview', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AnalyticsController::class, 'overview'])->name('overview');
        Route::get('/tenants', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AnalyticsController::class, 'tenants'])->name('tenants');
        Route::get('/users', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AnalyticsController::class, 'users'])->name('users');
        Route::get('/revenue', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AnalyticsController::class, 'revenue'])->name('revenue');
    });
    
    // System Settings
    Route::prefix('settings')->name('super-admin.settings.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SettingsController::class, 'index'])->name('index');
        Route::patch('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\SettingsController::class, 'update'])->name('update');
    });
    
    // User Impersonation
    Route::prefix('impersonate')->name('super-admin.impersonate.')->group(function () {
        Route::post('/{user}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ImpersonationController::class, 'start'])->name('start');
        Route::post('/stop', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ImpersonationController::class, 'stop'])->name('stop');
    });
    
    // Audit Logs
    Route::prefix('audit-logs')->name('super-admin.audit-logs.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AuditLogController::class, 'index'])->name('index');
        Route::get('/{log}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\AuditLogController::class, 'show'])->name('show');
    });

    // Product Management
    Route::prefix('products')->name('super-admin.products.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ProductController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ProductController::class, 'store'])->name('store');
        Route::get('/{product}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ProductController::class, 'show'])->name('show');
        Route::patch('/{product}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\ProductController::class, 'destroy'])->name('destroy');
    });

    // Plan Management
    Route::prefix('plans')->name('super-admin.plans.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\PlanController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\PlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\PlanController::class, 'show'])->name('show');
        Route::patch('/{plan}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\Amrshah\TenantEngine\Controllers\API\V1\SuperAdmin\PlanController::class, 'destroy'])->name('destroy');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant-Scoped Routes
|--------------------------------------------------------------------------
|
| These routes are scoped to a specific tenant. They use Stancl's
| InitializeTenancyByPath middleware to identify and switch to the
| correct tenant context.
|
*/

Route::middleware([
    InitializeTenancyByPath::class,
    PreventAccessFromCentralDomains::class,
    'auth:sanctum',
    'check_tenant_status',
])->group(function () {
    
    // Tenant Settings
    Route::prefix('settings')->name('tenant.settings.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\SettingsController::class, 'index'])->name('index');
        Route::patch('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\SettingsController::class, 'update'])->name('update');
    });
    
    // User Management
    Route::prefix('users')->name('tenant.users.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'store'])->name('store');
        Route::get('/{user}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'show'])->name('show');
        Route::patch('/{user}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'destroy'])->name('destroy');
        
        // User Actions
        Route::post('/{user}/invite', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'invite'])->name('invite');
        Route::post('/{user}/resend-invitation', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\UserController::class, 'resendInvitation'])->name('resend-invitation');
    });
    
    // Role Management
    Route::prefix('roles')->name('tenant.roles.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'show'])->name('show');
        Route::patch('/{role}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'destroy'])->name('destroy');
        
        // Role Actions
        Route::post('/{role}/assign-permissions', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\RoleController::class, 'assignPermissions'])->name('assign-permissions');
    });
    
    // Permission Management
    Route::prefix('permissions')->name('tenant.permissions.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\PermissionController::class, 'index'])->name('index');
        Route::post('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\PermissionController::class, 'store'])->name('store');
        Route::get('/{permission}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\PermissionController::class, 'show'])->name('show');
        Route::patch('/{permission}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\PermissionController::class, 'update'])->name('update');
        Route::delete('/{permission}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\PermissionController::class, 'destroy'])->name('destroy');
    });
    
    // Tenant Analytics
    Route::prefix('analytics')->name('tenant.analytics.')->group(function () {
        Route::get('/overview', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\AnalyticsController::class, 'overview'])->name('overview');
        Route::get('/users', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\AnalyticsController::class, 'users'])->name('users');
        Route::get('/activity', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\AnalyticsController::class, 'activity'])->name('activity');
    });
    
    // Tenant Audit Logs
    Route::prefix('audit-logs')->name('tenant.audit-logs.')->group(function () {
        Route::get('/', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\AuditLogController::class, 'index'])->name('index');
        Route::get('/{log}', [\Amrshah\TenantEngine\Controllers\API\V1\Tenant\AuditLogController::class, 'show'])->name('show');
    });
});

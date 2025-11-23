<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TenantEngine Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the TenantEngine package. When disabled, all
    | multi-tenancy features will be bypassed.
    |
    */

    'enabled' => env('TENANT_ENGINE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | External ID Prefixes
    |--------------------------------------------------------------------------
    |
    | Define custom prefixes for external IDs. These are used to generate
    | unique, non-sequential IDs for resources (e.g., USR_xxx, TNT_xxx).
    |
    */

    'external_id_prefixes' => [
        'users' => 'USR',
        'tenants' => 'TNT',
        'super_admins' => 'SAD',
        'oauth_providers' => 'OAP',
        'audit_logs' => 'AUD',
        // Add your custom prefixes here
    ],

    /*
    |--------------------------------------------------------------------------
    | External ID Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Nano ID generation.
    |
    */

    'external_id' => [
        'length' => 14,
        'alphabet' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
        'max_retries' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | General API settings including versioning and rate limiting.
    |
    */

    'api' => [
        'version' => '1.0.0',
        'prefix' => 'api',
        'latest_version' => 'v1',
        
        'rate_limits' => [
            'unauthenticated' => env('API_RATE_LIMIT_GUEST', 60),
            'authenticated' => env('API_RATE_LIMIT', 1000),
            'tenant' => env('API_RATE_LIMIT_TENANT', 10000),
            'super_admin' => env('API_RATE_LIMIT_SUPER_ADMIN', 50000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    |
    | Supported OAuth providers for social authentication.
    |
    */

    'oauth' => [
        'providers' => ['google', 'microsoft', 'linkedin', 'facebook'],
        
        'redirect_after_login' => env('OAUTH_REDIRECT_URL', '/dashboard'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Settings related to tenant management and database isolation.
    |
    */

    'tenant' => [
        'database_prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
        'default_plan' => 'free',
        'default_status' => 'active',
        
        'auto_create_database' => true,
        'auto_run_migrations' => true,
        'auto_seed_database' => false,
        'delete_database_on_delete' => false,
        
        'slug' => [
            'min_length' => 3,
            'max_length' => 50,
            'reserved' => [
                'api', 'admin', 'super-admin', 'www', 'mail', 'ftp',
                'localhost', 'staging', 'production', 'test', 'demo',
                'app', 'web', 'mobile', 'ios', 'android', 'dashboard',
                'auth', 'login', 'register', 'logout', 'password',
                'health', 'ping', 'version', 'status', 'docs', 'swagger',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for super admin functionality.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'role_name' => 'super_admin',
        'guard' => 'web',
        
        'impersonation' => [
            'enabled' => true,
            'session_key' => 'impersonated_by',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database connection settings for central and tenant databases.
    |
    */

    'database' => [
        'central' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
        
        'tenant' => [
            'connection' => 'tenant',
            'host' => env('TENANT_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('TENANT_DB_PORT', env('DB_PORT', '3306')),
            'username' => env('TENANT_DB_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('TENANT_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching settings for improved performance.
    |
    */

    'cache' => [
        'enabled' => true,
        'store' => env('TENANT_ENGINE_CACHE_STORE', 'redis'),
        'ttl' => env('TENANT_ENGINE_CACHE_TTL', 3600),
        'prefix' => 'tenant_engine',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable audit logging to track all actions in the system.
    |
    */

    'audit' => [
        'enabled' => env('TENANT_ENGINE_AUDIT_ENABLED', true),
        'log_super_admin' => true,
        'log_tenant_admin' => true,
        'log_tenant_user' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger/OpenAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for API documentation generation.
    |
    */

    'swagger' => [
        'enabled' => true,
        'route' => 'api/documentation',
        'title' => 'TenantEngine API Documentation',
        'description' => 'Multi-Tenant SaaS API',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'API Support',
            'email' => 'api@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings.
    |
    */

    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => false,
        
        'session_lifetime' => 120, // minutes
        'token_lifetime' => 60, // days
        
        'two_factor' => [
            'enabled' => false,
            'required_for_super_admin' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    |
    | Email verification settings.
    |
    */

    'email_verification' => [
        'enabled' => true,
        'required' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware aliases for the package.
    |
    */

    'middleware' => [
        'super_admin' => \Amrshah\TenantEngine\Middleware\EnsureSuperAdmin::class,
        'tenant_admin' => \Amrshah\TenantEngine\Http\Middleware\TenantAdminOnly::class,
        'identify_tenant' => \Stancl\Tenancy\Middleware\InitializeTenancyByPath::class,
        'check_tenant_status' => \Amrshah\TenantEngine\Http\Middleware\CheckTenantStatus::class,
        'validate_jsonapi' => \Amrshah\TenantEngine\Http\Middleware\ValidateJsonApi::class,
        'log_activity' => \Amrshah\TenantEngine\Http\Middleware\LogActivity::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Model class mappings. Override these if you extend the package models.
    |
    */

    'models' => [
        'user' => \App\Models\User::class,
        'tenant' => \Amrshah\TenantEngine\Models\Tenant::class,
        'super_admin' => \Amrshah\TenantEngine\Models\SuperAdmin::class,
        'oauth_provider' => \Amrshah\TenantEngine\Models\OAuthProvider::class,
        'audit_log' => \Amrshah\TenantEngine\Models\AuditLog::class,
    ],

];

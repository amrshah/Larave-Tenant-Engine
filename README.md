# TenantEngine - Multi-Tenant SaaS API Package for Laravel

[![Latest Version](https://img.shields.io/packagist/v/amrshah/tenant-engine.svg?style=flat-square)](https://packagist.org/packages/amrshah/tenant-engine)
[![Total Downloads](https://img.shields.io/packagist/dt/amrshah/tenant-engine.svg?style=flat-square)](https://packagist.org/packages/amrshah/tenant-engine)
[![License](https://img.shields.io/packagist/l/amrshah/tenant-engine.svg?style=flat-square)](https://packagist.org/packages/amrshah/tenant-engine)

**Production-ready Laravel package for building Multi-Tenant SaaS APIs with Super Admin and Tenant Admin capabilities.**

---

## ✨ Features

### 🏢 Multi-Tenancy
- ✅ **Path-based tenant identification** (`/tenant-slug/api/...`)
- ✅ **Automatic database isolation** per tenant (powered by Stancl/Tenancy)
- ✅ **Tenant-aware caching** (Redis)
- ✅ **Tenant-specific storage** (S3/local)
- ✅ **Complete tenant isolation**

### 🔐 Three-Level Access System
1. **Super Admin Level** - Manage all tenants, system analytics, global settings
2. **Central Level** - Authentication, tenant selection, user profile
3. **Tenant Level** - Tenant admin manages tenant resources

### 🛡️ Authentication & Authorization
- ✅ **Laravel Sanctum** - Token-based authentication
- ✅ **OAuth 2.0** - Google, Microsoft, LinkedIn, Facebook
- ✅ **Laravel ARBAC** - Advanced RBAC + ABAC hybrid
- ✅ **Multi-tenant user access** - Users can belong to multiple tenants
- ✅ **Email verification** & password reset

### 🆔 External ID System
- ✅ **Nano ID** with custom prefixes (`USR_xxx`, `TNT_xxx`, `CLI_xxx`)
- ✅ **Never expose internal database IDs**
- ✅ **URL-safe, collision-resistant**
- ✅ **Automatic generation** via model trait

### 📡 API Standards
- ✅ **JSON:API v1.1 compliant** responses
- ✅ **RESTful** design principles
- ✅ **API versioning** (`/api/v1/...`)
- ✅ **Cursor-based pagination** for scalability
- ✅ **Filtering, sorting, and including** relationships

### 📚 API Documentation
- ✅ **Swagger/OpenAPI 3.0** specification
- ✅ **Interactive API documentation** (Swagger UI)
- ✅ **Auto-generated** from code annotations

### 🏥 System Health Monitoring
- ✅ **Health check endpoints** (`/health`, `/ping`, `/version`, `/status`)
- ✅ **Database, Redis, Storage, Queue monitoring**
- ✅ **Performance metrics**

### 🛡️ Security
- ✅ **Rate limiting** (per user, per tenant, super admin)
- ✅ **CORS** configuration
- ✅ **Security headers** (CSP, X-Frame-Options, etc.)
- ✅ **SQL injection prevention** (Eloquent ORM)
- ✅ **XSS protection**

### ⚡ Performance
- ✅ **Redis caching** (tenant-aware)
- ✅ **Database query optimization** (indexes, eager loading)
- ✅ **Queue support** for heavy operations
- ✅ **Horizontal scalability**

---

## 📋 Requirements

- **PHP:** 8.1 or higher
- **Laravel:** 10.x, 11.x, or 12.x
- **MySQL:** 8.0+ or **PostgreSQL:** 13+
- **Redis:** 6.0+ (for caching and queues)
- **Composer:** 2.5+

### PHP Extensions
- BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, Redis

---

## 🚀 Installation

```bash
# Install via Composer
composer require amrshah/tenant-engine

# Publish configuration and migrations
php artisan vendor:publish --provider="Amrshah\TenantEngine\Providers\TenantEngineServiceProvider"

# Run installation command
php artisan tenant-engine:install
```

The installation command will:
- ✅ Publish configuration files
- ✅ Publish and run migrations
- ✅ Create default roles and permissions
- ✅ Generate Swagger documentation
- ✅ Set up example tenant (optional)

---

## ⚙️ Configuration

Add to your `.env` file:

```env
# Multi-Tenant Configuration
TENANT_ENGINE_ENABLED=true
TENANT_DB_PREFIX=tenant_

# OAuth Providers (Optional)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/api/v1/auth/oauth/google/callback"

MICROSOFT_CLIENT_ID=your-microsoft-client-id
MICROSOFT_CLIENT_SECRET=your-microsoft-client-secret
MICROSOFT_REDIRECT_URI="${APP_URL}/api/v1/auth/oauth/microsoft/callback"

# API Configuration
API_RATE_LIMIT=1000
API_RATE_LIMIT_TENANT=10000
```

---

## 📖 Quick Start

### Create Super Admin

```bash
php artisan tenant-engine:create-super-admin \
    --name="Admin" \
    --email="admin@example.com" \
    --password="SecurePassword123!"
```

### Create First Tenant

```bash
php artisan tenant-engine:tenant:create \
    --name="Acme Corporation" \
    --email="admin@acme.com" \
    --slug="acme-corp"
```

### Access API Documentation

Navigate to:
```
http://localhost:8000/api/documentation
```

---

## 🔌 API Endpoints

### Super Admin APIs

```http
# Tenant Management
GET    /api/v1/super-admin/tenants
POST   /api/v1/super-admin/tenants
GET    /api/v1/super-admin/tenants/{tenant}
PATCH  /api/v1/super-admin/tenants/{tenant}
DELETE /api/v1/super-admin/tenants/{tenant}
POST   /api/v1/super-admin/tenants/{tenant}/suspend
POST   /api/v1/super-admin/tenants/{tenant}/activate

# System Analytics
GET    /api/v1/super-admin/analytics/overview
GET    /api/v1/super-admin/analytics/tenants
GET    /api/v1/super-admin/analytics/users

# User Impersonation
POST   /api/v1/super-admin/impersonate/{user}
POST   /api/v1/super-admin/stop-impersonation
```

### Central APIs

```http
# Authentication
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/auth/me

# OAuth
GET    /api/v1/auth/oauth/{provider}
GET    /api/v1/auth/oauth/{provider}/callback

# Tenant Selection
GET    /api/v1/tenants
POST   /api/v1/tenants/{tenant}/switch
```

### Tenant Admin APIs

```http
# User Management
GET    /{tenant}/api/v1/users
POST   /{tenant}/api/v1/users
GET    /{tenant}/api/v1/users/{user}
PATCH  /{tenant}/api/v1/users/{user}
DELETE /{tenant}/api/v1/users/{user}

# Role & Permission Management
GET    /{tenant}/api/v1/roles
POST   /{tenant}/api/v1/roles
GET    /{tenant}/api/v1/permissions

# Tenant Settings
GET    /{tenant}/api/v1/settings
PATCH  /{tenant}/api/v1/settings
```

### System Health

```http
GET    /api/v1/health    # System health check
GET    /api/v1/ping      # Simple ping
GET    /api/v1/version   # Version info
GET    /api/v1/status    # System status (authenticated)
```

---

## 📚 Documentation

- [Installation Guide](docs/installation.md)
- [Configuration Guide](docs/configuration.md)
- [Super Admin Guide](docs/super-admin-guide.md)
- [Tenant Admin Guide](docs/tenant-admin-guide.md)
- [API Reference](docs/api-reference.md)
- [Deployment Guide](docs/deployment.md)

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run Pest tests
composer test:pest

# Run static analysis
composer analyse

# Format code
composer format
```

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## 🔒 Security

If you discover any security-related issues, please email security@amrshah.dev instead of using the issue tracker.

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

---

## 👨‍💻 Author

**Ali Raza** (a.k.a Amr Shah)

- GitHub: [@amrshah](https://github.com/amrshah)
- Email: ali@amrshah.dev
- Website: [amrshah.dev](https://amrshah.dev)

---

## 🙏 Acknowledgments

This package is built on top of excellent open-source packages:

- [Laravel](https://laravel.com) - The PHP framework
- [Stancl/Tenancy](https://tenancyforlaravel.com) - Multi-tenancy for Laravel
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API authentication
- [Laravel Socialite](https://laravel.com/docs/socialite) - OAuth authentication
- [Laravel ARBAC](https://github.com/amrshah/laravel-arbac) - Advanced RBAC + ABAC
- [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) - Swagger documentation
- [Nano ID](https://github.com/ai/nanoid) - Unique ID generator

---

**Made with ❤️ by Ali Raza (Amr Shah)**

**Version:** 1.0.0-alpha  
**Last Updated:** 2025-11-23

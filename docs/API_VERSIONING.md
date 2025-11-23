# API Versioning Strategy

## Overview

The TenantEngine API uses **URL-based versioning** with the format `/api/v{version}/...`. This document outlines our versioning strategy and guidelines.

## Current Version

- **Latest Version:** v1
- **Supported Versions:** v1
- **Deprecated Versions:** None

## Versioning Format

```
https://api.example.com/api/v1/resource
                          ^^
                          Version Number
```

## Version Lifecycle

### 1. Active Version (v1)
- Receives new features
- Gets bug fixes and security patches
- Fully supported and documented

### 2. Deprecated Version
- Receives only critical security patches
- No new features
- Marked with deprecation headers
- Minimum 6 months notice before removal

### 3. Sunset Version
- No longer supported
- Returns 410 Gone status
- Redirects to migration guide

## Breaking Changes Policy

A new major version (v2, v3, etc.) is created when introducing **breaking changes**:

### What Constitutes a Breaking Change?

- Removing or renaming endpoints
- Changing request/response structure
- Modifying authentication requirements
- Changing error response formats
- Removing required fields
- Changing data types

### What is NOT a Breaking Change?

- Adding new endpoints
- Adding optional fields
- Adding new response fields
- Deprecating (but not removing) fields
- Bug fixes that correct incorrect behavior

## Version Headers

All API responses include version information:

```http
X-API-Version: v1
X-API-Latest-Version: v1
X-API-Deprecated: false
```

For deprecated versions:
```http
X-API-Version: v1
X-API-Latest-Version: v2
X-API-Deprecated: true
X-API-Sunset-Date: 2025-06-01
Link: <https://docs.example.com/migration/v1-to-v2>; rel="deprecation"
```

## Migration Path

### When Upgrading from v1 to v2:

1. **Review Migration Guide:** Check `/docs/migration/v1-to-v2`
2. **Test in Sandbox:** Use test environment with v2
3. **Update Client Code:** Modify API calls to use v2 endpoints
4. **Monitor Deprecation Headers:** Watch for deprecation warnings
5. **Complete Migration:** Switch production traffic to v2

## Implementation

### Route Structure

```php
// v1 Routes (Current)
Route::prefix('api/v1')->group(function () {
    // All v1 endpoints
});

// v2 Routes (Future)
Route::prefix('api/v2')->group(function () {
    // All v2 endpoints
});
```

### Version Detection Middleware

```php
class ApiVersionMiddleware
{
    public function handle($request, Closure $next)
    {
        $version = $request->route()->getPrefix();
        
        // Add version headers
        return $next($request)->withHeaders([
            'X-API-Version' => $version,
            'X-API-Latest-Version' => 'v1',
        ]);
    }
}
```

### Deprecation Notice

When deprecating v1:

```php
if ($version === 'v1') {
    return $next($request)->withHeaders([
        'X-API-Deprecated' => 'true',
        'X-API-Sunset-Date' => '2025-06-01',
        'Link' => '<https://docs.example.com/migration/v1-to-v2>; rel="deprecation"',
    ]);
}
```

## Backward Compatibility

### Field Deprecation

Instead of removing fields immediately:

```json
{
  "data": {
    "type": "tenants",
    "id": "TNT_abc123",
    "attributes": {
      "name": "Acme Corp",
      "slug": "acme-corp",  // Deprecated in v2
      "identifier": "acme-corp"  // New field in v2
    }
  }
}
```

### Endpoint Deprecation

```php
// v1 - Deprecated endpoint
Route::get('/tenants/{id}', [TenantController::class, 'show'])
    ->middleware('deprecated:2025-06-01');

// v2 - New endpoint
Route::get('/tenants/{external_id}', [TenantController::class, 'show']);
```

## Client Integration

### Recommended Client Code

```javascript
const API_VERSION = 'v1';
const BASE_URL = `https://api.example.com/api/${API_VERSION}`;

async function apiRequest(endpoint, options = {}) {
  const response = await fetch(`${BASE_URL}${endpoint}`, options);
  
  // Check for deprecation
  if (response.headers.get('X-API-Deprecated') === 'true') {
    console.warn(
      `API version ${API_VERSION} is deprecated. ` +
      `Sunset date: ${response.headers.get('X-API-Sunset-Date')}`
    );
  }
  
  return response.json();
}
```

## Documentation

Each version maintains separate documentation:

- `/docs/api/v1` - Version 1 documentation
- `/docs/api/v2` - Version 2 documentation
- `/docs/migration/v1-to-v2` - Migration guide

## Changelog

All version changes are documented in `CHANGELOG.md`:

```markdown
## [2.0.0] - 2025-01-15

### Breaking Changes
- Changed tenant identifier from `slug` to `external_id`
- Removed deprecated `status` field from user resource

### Added
- New `/api/v2/analytics` endpoint
- Support for bulk operations

### Migration
See [Migration Guide](docs/migration/v1-to-v2.md)
```

## Support Timeline

| Version | Release Date | Deprecation Date | Sunset Date |
|---------|--------------|------------------|-------------|
| v1      | 2024-11-01   | TBD              | TBD         |
| v2      | TBD          | TBD              | TBD         |

## Best Practices

1. **Always specify version** in API calls
2. **Monitor deprecation headers** in responses
3. **Subscribe to API changelog** for updates
4. **Test new versions** in sandbox before production
5. **Plan migrations** well before sunset dates
6. **Use semantic versioning** for client libraries

## Questions?

For questions about API versioning:
- Email: api-support@example.com
- Docs: https://docs.example.com/api/versioning
- Slack: #api-support

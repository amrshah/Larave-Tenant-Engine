# TODO: HttpOnly Cookie Authentication

## Vendor Package Update Required

**Package**: `amrshah/tenant-engine`

### Changes Needed

The `AuthController` in the vendor package needs to be updated to support HttpOnly cookies by default.

**Current Implementation**: Returns tokens in JSON response body
**Required Implementation**: Set tokens in HttpOnly cookies

### Temporary Solution

We've created an override in `app/Http/Controllers/API/V1/Auth/AuthController.php` that extends the vendor package's AuthController and implements HttpOnly cookie support.

### Action Items

1. [ ] Update `amrshah/tenant-engine` package AuthController to support HttpOnly cookies
2. [ ] Add configuration option to toggle between cookie-based and token-based auth
3. [ ] Once package is updated, remove the override in `app/Http/Controllers/API/V1/Auth/AuthController.php`
4. [ ] Update routes in `routes/tenant.php` to use vendor controller again

### Files Modified (Temporary Override)

- `app/Http/Controllers/API/V1/Auth/AuthController.php` - Override with HttpOnly cookie support
- `routes/tenant.php` - Updated to use app controller instead of vendor controller
- `config/sanctum.php` - Added stateful domain configuration
- `config/cors.php` - Added frontend domain to allowed origins

### Security Benefits

- ✅ Protection against XSS attacks (cookies not accessible via JavaScript)
- ✅ Automatic CSRF protection with SameSite=Lax
- ✅ Secure flag ensures HTTPS-only transmission
- ✅ HttpOnly flag prevents client-side access

---

## Frontend Changes

All frontend changes are permanent and don't require vendor package updates:

- `lib/api-client.ts` - Removed token interceptor (cookies sent automatically)
- `lib/services/auth.service.ts` - Removed token extraction from responses
- `lib/auth-context.tsx` - Removed token state and localStorage handling
- `components/auth/login-form.tsx` - Updated to work with cookie-based auth

---

**Priority**: Medium
**Estimated Effort**: 2-3 hours (for vendor package update)
**Created**: 2026-01-12

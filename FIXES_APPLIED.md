# Fixes Applied to Job Portal API

## Date: October 7, 2025

### Issue: 403 Forbidden on Admin Routes

**Problem:**
Admin routes were returning 403 Forbidden errors even with valid authentication tokens. This was because Sanctum was authenticating any user type (Employee, Employer, or Admin) with the same middleware, and there was no check to ensure only Admin users could access admin routes.

**Solution:**
Created a custom middleware `EnsureUserIsAdmin` that verifies the authenticated user is specifically an Admin model instance.

**Files Created/Modified:**

1. **Created:** `app/Http/Middleware/EnsureUserIsAdmin.php`
   - Checks if authenticated user is an instance of Admin model
   - Returns 403 if user is not an admin

2. **Modified:** `app/Http/Kernel.php`
   - Added `'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class` to route middleware

3. **Modified:** `routes/api.php`
   - Updated admin routes to use both `auth:sanctum` and `admin` middleware
   - Changed from: `middleware('auth:sanctum')`
   - Changed to: `middleware(['auth:sanctum', 'admin'])`

**How It Works:**
```
Request → auth:sanctum (checks token) → admin middleware (checks if Admin model) → AdminController
```

**Testing:**
- Login with admin credentials
- All admin endpoints should now work correctly
- Employee/Employer tokens will be rejected with 403 on admin routes

**Admin Routes Protected:**
- `/api/v1/admin/profile`
- `/api/v1/admin/dashboard/stats`
- `/api/v1/admin/admins/*`
- `/api/v1/admin/employees/*`
- `/api/v1/admin/employers/*`
- `/api/v1/admin/jobs`
- `/api/v1/admin/coupons`
- `/api/v1/admin/commissions/*`
- `/api/v1/admin/cv-requests/*`

**No Changes Required to Frontend:**
The admin panel frontend (`admin-panel`) doesn't need any code changes. The authentication flow remains the same.

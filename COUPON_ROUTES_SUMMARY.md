# Coupon API Routes - Quick Reference

## Admin Routes (Requires Admin Authentication)
Base URL: `/api/v1/admin`

| Method | Endpoint | Controller Method | Access | Description |
|--------|----------|-------------------|--------|-------------|
| POST | `/coupons` | createCoupon | Staff, Manager, Super Admin | Create new coupon (pending status) |
| GET | `/coupons` | getCoupons | Staff, Manager, Super Admin | Get coupons (role-based filtering) |
| GET | `/coupons/pending` | getPendingCoupons | Super Admin | Get all pending coupons |
| GET | `/coupons/{id}` | getCoupon | Staff, Manager, Super Admin | Get single coupon details |
| PUT | `/coupons/{id}/approve` | approveCoupon | Super Admin | Approve or reject coupon |
| POST | `/coupons/{id}/assign-users` | assignUsers | Staff, Manager, Super Admin | Assign users to approved coupon |
| DELETE | `/coupons/{couponId}/users/{assignmentId}` | removeUser | Staff, Manager, Super Admin | Remove user from coupon |
| DELETE | `/coupons/{id}` | deleteCoupon | Super Admin | Delete coupon (no users assigned) |

## Public Routes (Requires Employee/Employer Authentication)
Base URL: `/api/v1/coupons`

| Method | Endpoint | Controller | Access | Description |
|--------|----------|------------|--------|-------------|
| POST | `/validate` | PaymentController@validateCoupon | Employee, Employer | Validate coupon for plan upgrade |

## Request/Response Examples

### Create Coupon
```bash
POST /api/v1/admin/coupons
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "SUMMER2025",
  "name": "Summer Special Discount",
  "discount_percentage": 25,
  "coupon_for": "employee",
  "expiry_date": "2025-08-31"
}
```

### Approve Coupon
```bash
PUT /api/v1/admin/coupons/{coupon-id}/approve
Authorization: Bearer {super_admin_token}
Content-Type: application/json

{
  "status": "approved"
}
```

### Assign Users
```bash
POST /api/v1/admin/coupons/{coupon-id}/assign-users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "users": [
    {"identifier": "employee@example.com", "type": "employee"},
    {"identifier": "9876543210", "type": "employee"}
  ]
}
```

### Validate Coupon (Employee/Employer)
```bash
POST /api/v1/coupons/validate
Authorization: Bearer {employee_or_employer_token}
Content-Type: application/json

{
  "coupon_code": "SUMMER2025",
  "plan_id": "{plan-uuid}"
}
```

## Query Parameters

### GET /api/v1/admin/coupons
- `status` (optional): `pending`, `approved`, `rejected`
- `coupon_for` (optional): `employee`, `employer`

Example:
```bash
GET /api/v1/admin/coupons?status=approved&coupon_for=employee
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation error) |
| 403 | Forbidden (unauthorized access) |
| 404 | Not Found |
| 422 | Unprocessable Entity (validation failed) |
| 500 | Server Error |

## Role Permissions Matrix

| Action | Staff | Manager | Super Admin |
|--------|-------|---------|-------------|
| Create Coupon | ✓ | ✓ | ✓ |
| View Own Coupons | ✓ | ✓ | ✓ |
| View Staff Coupons | ✗ | ✓ | ✓ |
| View All Coupons | ✗ | ✗ | ✓ |
| Approve/Reject | ✗ | ✗ | ✓ |
| Assign Users (Own) | ✓ | ✓ | ✓ |
| Assign Users (Staff) | ✗ | ✓ | ✓ |
| Assign Users (All) | ✗ | ✗ | ✓ |
| Delete Coupon | ✗ | ✗ | ✓ |

## Model Relationships

### Coupon Model
- `creator()` - BelongsTo Admin (created_by)
- `approver()` - BelongsTo Admin (approved_by)
- `employees()` - BelongsToMany Employee
- `employers()` - BelongsToMany Employer
- `assignedUsers()` - HasMany CouponUser

### CouponUser Model
- `coupon()` - BelongsTo Coupon
- `assigner()` - BelongsTo Admin
- `user()` - Polymorphic (Employee or Employer)

### Admin Model
- `coupons()` - HasMany Coupon (created_by)
- `manager()` - BelongsTo Admin
- `staff()` - HasMany Admin

## Database Schema

### coupons table
```sql
id (uuid, primary)
code (string, unique)
name (string)
discount_percentage (decimal)
coupon_for (enum: employee, employer)
expiry_date (date)
created_by (uuid, foreign -> admins)
status (enum: pending, approved, rejected)
approved_by (uuid, foreign -> admins, nullable)
approved_at (timestamp, nullable)
created_at, updated_at
```

### coupon_users table
```sql
id (uuid, primary)
coupon_id (uuid, foreign -> coupons)
user_id (uuid)
user_type (enum: employee, employer)
assigned_by (uuid, foreign -> admins)
assigned_at (timestamp)
created_at, updated_at
unique(coupon_id, user_id, user_type)
```

## Migration Files
1. `2025_10_22_105230_update_coupons_table_add_new_fields.php`
2. `2025_10_22_105237_create_coupon_users_table.php`

## Files Modified/Created

### New Files
- `app/Http/Controllers/Api/CouponController.php`
- `app/Models/CouponUser.php`
- `database/migrations/2025_10_22_105230_update_coupons_table_add_new_fields.php`
- `database/migrations/2025_10_22_105237_create_coupon_users_table.php`

### Modified Files
- `app/Models/Coupon.php` - Added new fields and relationships
- `app/Models/Admin.php` - Updated coupons relationship
- `app/Http/Controllers/Api/AdminController.php` - Removed old coupon methods
- `app/Http/Controllers/Api/PaymentController.php` - Updated validateCoupon method
- `routes/api.php` - Updated coupon routes

## Testing with Postman/Thunder Client

1. **Create a super admin user** (if not exists)
2. **Login as super admin** to get token
3. **Create staff/manager users** (if testing role permissions)
4. **Create a coupon** as staff/manager/super_admin
5. **Approve the coupon** as super_admin
6. **Assign users** to the approved coupon
7. **Validate coupon** as employee/employer
8. **Test redemption** during plan upgrade

## Common Use Cases

### Use Case 1: Staff Creates and Assigns Coupon
1. Staff creates coupon → Status: pending
2. Super Admin approves coupon → Status: approved
3. Staff assigns employees by email
4. Employees can use coupon during plan upgrade

### Use Case 2: Manager Manages Team Coupons
1. Manager views all coupons (own + staff's)
2. Manager creates new coupon → Status: pending
3. Super Admin approves
4. Manager assigns users to approved coupon

### Use Case 3: Super Admin Bulk Operations
1. Super Admin views all pending coupons
2. Super Admin bulk approves multiple coupons
3. Super Admin assigns users to any approved coupon
4. Super Admin monitors all coupon usage

## Notes
- All migrations have been successfully run
- The system is fully integrated and ready for testing
- Frontend integration points are documented
- Role-based access control is enforced at controller level

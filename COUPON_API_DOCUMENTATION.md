# Coupon Management System - API Documentation

## Overview
The coupon management system has been completely redesigned with role-based access control, approval workflow, and user assignment features.

## Database Changes

### Coupons Table
New fields added:
- `name` - Coupon name/description
- `coupon_for` - Type of users (employee/employer)
- `status` - Coupon status (pending/approved/rejected)
- `created_by` - Admin who created the coupon (renamed from staff_id)
- `approved_by` - Super admin who approved
- `approved_at` - Approval timestamp

### Coupon Users Table (New)
Pivot table for assigning coupons to specific users:
- `coupon_id` - Reference to coupon
- `user_id` - Employee or Employer ID
- `user_type` - Type (employee/employer)
- `assigned_by` - Admin who assigned the user
- `assigned_at` - Assignment timestamp

## Workflow

1. **Creation**: Staff, Manager, or Super Admin creates a coupon (status: pending)
2. **Approval**: Only Super Admin can approve or reject coupons
3. **Assignment**: After approval, Staff/Manager/Super Admin can assign employees/employers to the coupon
4. **Redemption**: Only assigned users can use the coupon when upgrading their plan

## API Endpoints

### 1. Create Coupon
**Endpoint**: `POST /api/v1/admin/coupons`
**Auth**: Admin (staff, manager, super_admin)

**Request Body**:
```json
{
  "code": "SAVE50",
  "name": "50% Discount for Premium Users",
  "discount_percentage": 50,
  "coupon_for": "employee",
  "expiry_date": "2025-12-31"
}
```

**Response**:
```json
{
  "message": "Coupon created successfully and pending approval",
  "coupon": {
    "id": "uuid",
    "code": "SAVE50",
    "name": "50% Discount for Premium Users",
    "discount_percentage": 50,
    "coupon_for": "employee",
    "expiry_date": "2025-12-31",
    "status": "pending",
    "created_by": "admin-uuid",
    "creator": {...}
  }
}
```

### 2. Get All Coupons
**Endpoint**: `GET /api/v1/admin/coupons`
**Auth**: Admin (staff, manager, super_admin)

**Query Parameters**:
- `status` (optional): pending, approved, rejected
- `coupon_for` (optional): employee, employer

**Role-based filtering**:
- Staff: Only their own coupons
- Manager: Their own + assigned staff's coupons
- Super Admin: All coupons

**Response**:
```json
{
  "coupons": [...],
  "count": 10
}
```

### 3. Get Single Coupon
**Endpoint**: `GET /api/v1/admin/coupons/{id}`
**Auth**: Admin (staff, manager, super_admin)

**Response**:
```json
{
  "coupon": {...},
  "assigned_users": [
    {
      "id": "assignment-uuid",
      "user_id": "user-uuid",
      "user_type": "employee",
      "user_data": {
        "id": "user-uuid",
        "name": "John Doe",
        "email": "john@example.com",
        "mobile": "1234567890"
      },
      "assigned_by": {...},
      "assigned_at": "2025-10-22T10:30:00Z"
    }
  ]
}
```

### 4. Get Pending Coupons
**Endpoint**: `GET /api/v1/admin/coupons/pending`
**Auth**: Super Admin only

**Response**:
```json
{
  "coupons": [...],
  "count": 5
}
```

### 5. Approve/Reject Coupon
**Endpoint**: `PUT /api/v1/admin/coupons/{id}/approve`
**Auth**: Super Admin only

**Request Body**:
```json
{
  "status": "approved"
}
```

**Response**:
```json
{
  "message": "Coupon approved successfully",
  "coupon": {...}
}
```

### 6. Assign Users to Coupon
**Endpoint**: `POST /api/v1/admin/coupons/{id}/assign-users`
**Auth**: Admin (staff, manager, super_admin)

**Request Body**:
```json
{
  "users": [
    {
      "identifier": "john@example.com",
      "type": "employee"
    },
    {
      "identifier": "9876543210",
      "type": "employee"
    }
  ]
}
```

**Response**:
```json
{
  "message": "User assignment completed",
  "assigned": [
    {
      "id": "assignment-uuid",
      "user_id": "user-uuid",
      "user_type": "employee",
      "user_name": "John Doe",
      "user_email": "john@example.com"
    }
  ],
  "failed": [
    {
      "identifier": "notfound@example.com",
      "type": "employee",
      "reason": "User not found"
    }
  ],
  "assigned_count": 1,
  "failed_count": 1
}
```

### 7. Remove User from Coupon
**Endpoint**: `DELETE /api/v1/admin/coupons/{couponId}/users/{assignmentId}`
**Auth**: Admin (staff, manager, super_admin)

**Response**:
```json
{
  "message": "User removed from coupon successfully"
}
```

### 8. Delete Coupon
**Endpoint**: `DELETE /api/v1/admin/coupons/{id}`
**Auth**: Super Admin only

**Note**: Can only delete coupons with no assigned users

**Response**:
```json
{
  "message": "Coupon deleted successfully"
}
```

### 9. Validate Coupon (For Employees/Employers)
**Endpoint**: `POST /api/v1/coupons/validate`
**Auth**: Employee or Employer

**Request Body**:
```json
{
  "coupon_code": "SAVE50",
  "plan_id": "plan-uuid"
}
```

**Response** (Valid):
```json
{
  "valid": true,
  "coupon": {
    "code": "SAVE50",
    "name": "50% Discount for Premium Users",
    "discount_percentage": 50,
    "expiry_date": "2025-12-31"
  },
  "plan": {
    "price": 1000
  },
  "discount_amount": "500.00",
  "final_amount": "500.00"
}
```

**Response** (Invalid):
```json
{
  "valid": false,
  "message": "This coupon is not available for your account"
}
```

## Validation Rules

### Coupon Creation
- Code must be unique and uppercase
- Discount percentage: 0-100
- Coupon_for: employee or employer
- Expiry date must be today or future date

### Coupon Approval
- Only pending coupons can be approved/rejected
- Only Super Admin can approve

### User Assignment
- Coupon must be approved
- User must exist (searched by email or phone)
- Coupon type must match user type
- User cannot be assigned twice to same coupon

### Coupon Validation (Redemption)
- Coupon must be approved
- Coupon must not be expired
- User must be assigned to the coupon
- Coupon type must match user type
- Plan type must match coupon type

## Role-Based Access Control

### Staff
- Create coupons
- View only their own coupons
- Assign users to their own approved coupons

### Manager
- Create coupons
- View their own and assigned staff's coupons
- Assign users to their own and assigned staff's approved coupons

### Super Admin
- Create coupons
- View all coupons
- Approve/Reject coupons
- Assign users to any approved coupon
- Delete coupons (only if no users assigned)

## Error Messages

- "Coupon not found" (404)
- "Unauthorized to view this coupon" (403)
- "Only pending coupons can be approved or rejected" (400)
- "Only approved coupons can have users assigned" (400)
- "This coupon is only for {type}s" (200 - validation)
- "User not found" (in failed array)
- "User already assigned to this coupon" (in failed array)
- "This coupon is not available for your account" (200 - validation)
- "Cannot delete coupon with assigned users" (400)

## Integration with Frontend

The frontend admin panel should:

1. **Coupons Page** (`/coupons`):
   - List all coupons (filtered by role)
   - Create new coupon button
   - Filter by status and coupon_for
   - Show status badges (pending/approved/rejected)

2. **Pending Approvals** (Super Admin only):
   - List pending coupons
   - Approve/Reject buttons

3. **Coupon Details Page**:
   - Show coupon information
   - Show approval status and approver
   - List assigned users
   - Add users form (email/phone input)
   - Remove user button for each assigned user

4. **Employee/Employer Plan Upgrade**:
   - Coupon code input field
   - Validate coupon before payment
   - Show discount details

## Testing Checklist

- [ ] Staff can create coupons
- [ ] Manager can create coupons
- [ ] Super Admin can create coupons
- [ ] Staff can only see their own coupons
- [ ] Manager can see their own and staff's coupons
- [ ] Super Admin can see all coupons
- [ ] Only Super Admin can approve coupons
- [ ] Cannot assign users to pending coupons
- [ ] Can assign users by email
- [ ] Can assign users by phone number
- [ ] Cannot assign same user twice
- [ ] Employee can only use employee coupons
- [ ] Employer can only use employer coupons
- [ ] Only assigned users can redeem coupons
- [ ] Expired coupons cannot be used
- [ ] Rejected coupons cannot be used
- [ ] Pending coupons cannot be used

## Notes

- All coupon codes are automatically converted to uppercase
- The system uses UUID for all IDs
- Coupon assignment supports bulk operations (multiple users at once)
- Failed assignments are reported separately without failing the entire operation
- Database migrations have been run successfully
- The old coupon methods in AdminController have been removed

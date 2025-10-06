# Job Portal - Admin Panel API Documentation

**Version:** 1.0
**Last Updated:** October 6, 2025
**Status:** Production Ready ✅
**Base URL:** `http://localhost:8000/api/v1`

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Authentication](#authentication)
3. [Admin Endpoints Overview](#admin-endpoints-overview)
4. [Dashboard & Statistics](#dashboard--statistics)
5. [Admin Management](#admin-management)
6. [Employee Management](#employee-management)
7. [Employer Management](#employer-management)
8. [Job Management](#job-management)
9. [Coupon Management](#coupon-management)
10. [Commission Management](#commission-management)
11. [CV Request Management](#cv-request-management)
12. [Plan Management](#plan-management)
13. [Catalog Management](#catalog-management)
14. [Error Handling](#error-handling)

---

## Quick Start

### Authentication Setup

```javascript
// Store token after admin login
localStorage.setItem('auth_token', response.token);
localStorage.setItem('user_type', 'admin');
localStorage.setItem('admin_role', response.user.role);

// Include in all authenticated requests
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};
```

### Admin Roles

- **super_admin**: Full access to all features
- **employee_manager**: Manage employees
- **employer_manager**: Manage employers
- **plan_upgrade_manager**: Manage plans and subscriptions
- **catalog_manager**: Manage industries, locations, categories

---

## Authentication

### Admin Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "identifier": "admin@jobportal.com",
  "password": "SecurePass123!"
}
```

**Response:**
```json
{
  "token": "5|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user_type": "admin",
  "user": {
    "id": "uuid-here",
    "name": "Admin User",
    "email": "admin@jobportal.com",
    "role": "super_admin"
  }
}
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## Admin Endpoints Overview

| Endpoint | Method | Auth Level | Description |
|----------|--------|------------|-------------|
| `/admin/profile` | GET | All Admins | Get admin profile |
| `/admin/dashboard/stats` | GET | All Admins | Get dashboard statistics |
| `/admin/admins` | GET | Super Admin | List all admins |
| `/admin/admins/{id}` | GET | Super Admin | Get single admin |
| `/admin/admins` | POST | Super Admin | Create admin |
| `/admin/admins/{id}` | PUT | Super Admin | Update admin |
| `/admin/admins/{id}` | DELETE | Super Admin | Delete admin |
| `/admin/employees` | GET | Admin | List employees |
| `/admin/employees/{id}` | GET | Admin | Get employee |
| `/admin/employees/{id}` | PUT | Admin | Update employee |
| `/admin/employees/{id}` | DELETE | Admin | Delete employee |
| `/admin/employers` | GET | Admin | List employers |
| `/admin/employers/{id}` | GET | Admin | Get employer |
| `/admin/employers/{id}` | PUT | Admin | Update employer |
| `/admin/employers/{id}` | DELETE | Admin | Delete employer |
| `/admin/jobs` | GET | Admin | List all jobs |
| `/admin/coupons` | GET | Super Admin | List coupons |
| `/admin/coupons` | POST | Super Admin | Create coupon |
| `/admin/commissions/all` | GET | Super Admin | Get all commissions |
| `/admin/commissions/my` | GET | Staff | Get staff commissions |
| `/admin/commissions/manual` | POST | Admin | Add manual commission |
| `/admin/cv-requests` | GET | Admin | Get CV requests |
| `/admin/cv-requests/{id}/status` | PUT | Admin | Update CV request status |

---

## Dashboard & Statistics

### Get Admin Profile

```http
GET /api/v1/admin/profile
Authorization: Bearer {token}
```

**Response:**
```json
{
  "admin": {
    "id": "uuid-here",
    "name": "Admin User",
    "email": "admin@jobportal.com",
    "role": "super_admin"
  }
}
```

### Get Dashboard Statistics

```http
GET /api/v1/admin/dashboard/stats
Authorization: Bearer {token}
```

**Super Admin Response:**
```json
{
  "total_employees": 1250,
  "total_employers": 340,
  "total_jobs": 890,
  "active_jobs": 645,
  "total_applications": 5420,
  "pending_cv_requests": 23,
  "total_commissions": "15750.50",
  "total_coupons": 45
}
```

**Other Admins Response (no commissions/coupons):**
```json
{
  "total_employees": 1250,
  "total_employers": 340,
  "total_jobs": 890,
  "active_jobs": 645,
  "total_applications": 5420,
  "pending_cv_requests": 23
}
```

---

## Admin Management

**Auth Required:** Super Admin Only

### List All Admins

```http
GET /api/v1/admin/admins
Authorization: Bearer {token}
```

**Response:**
```json
{
  "admins": [
    {
      "id": "uuid-here",
      "name": "Admin User",
      "email": "admin@jobportal.com",
      "role": "super_admin",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Get Single Admin

```http
GET /api/v1/admin/admins/{id}
Authorization: Bearer {token}
```

### Create Admin

```http
POST /api/v1/admin/admins
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Manager",
  "email": "jane@admin.com",
  "password": "SecurePass123!",
  "role": "employer_manager"
}
```

**Valid Roles:**
- `super_admin`
- `employee_manager`
- `employer_manager`
- `plan_upgrade_manager`
- `catalog_manager`

**Response (201 Created):**
```json
{
  "message": "Admin created successfully",
  "admin": {
    "id": "uuid-here",
    "name": "Jane Manager",
    "email": "jane@admin.com",
    "role": "employer_manager"
  }
}
```

### Update Admin

```http
PUT /api/v1/admin/admins/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Updated",
  "role": "employee_manager",
  "password": "NewPassword123!"
}
```

**Note:** Password is optional. Only include if changing password.

**Response:**
```json
{
  "message": "Admin updated successfully"
}
```

### Delete Admin

```http
DELETE /api/v1/admin/admins/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Admin deleted successfully"
}
```

**Error:** Cannot delete your own account (403 Forbidden)

---

## Employee Management

**Auth Required:** Admin

### List Employees

```http
GET /api/v1/admin/employees?page=1&search=john
Authorization: Bearer {token}
```

**Response:**
```json
{
  "employees": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "mobile": "+1234567890",
        "gender": "M",
        "dob": "1990-05-15",
        "plan_id": "uuid-plan",
        "created_at": "2024-01-15T10:30:00.000000Z"
      }
    ],
    "total": 1250,
    "per_page": 20,
    "last_page": 63
  }
}
```

### Get Single Employee

```http
GET /api/v1/admin/employees/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "employee": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "mobile": "+1234567890",
    "gender": "M",
    "dob": "1990-05-15",
    "address": {...},
    "education_details": [...],
    "experience_details": [...],
    "skills_details": [...],
    "cv_url": "/storage/cvs/cv_123.pdf",
    "plan": {
      "name": "Basic Employee Plan",
      "price": "9.99"
    }
  }
}
```

### Update Employee

```http
PUT /api/v1/admin/employees/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "mobile": "+1987654321",
  "address": {...}
}
```

**Response:**
```json
{
  "message": "Employee updated successfully"
}
```

### Delete Employee

```http
DELETE /api/v1/admin/employees/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Employee deleted successfully"
}
```

---

## Employer Management

**Auth Required:** Admin

### List Employers

```http
GET /api/v1/admin/employers?page=1&search=tech
Authorization: Bearer {token}
```

**Response:**
```json
{
  "employers": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "company_name": "Tech Innovations Inc.",
        "email": "hr@techinnovations.com",
        "contact": "+1234567890",
        "industry_type": "uuid-industry",
        "plan_id": "uuid-plan",
        "created_at": "2024-02-10T14:20:00.000000Z"
      }
    ],
    "total": 340,
    "per_page": 20,
    "last_page": 17
  }
}
```

### Get Single Employer

```http
GET /api/v1/admin/employers/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "employer": {
    "id": "uuid-here",
    "company_name": "Tech Innovations Inc.",
    "email": "hr@techinnovations.com",
    "contact": "+1234567890",
    "address": {...},
    "industry_type": "uuid-industry",
    "plan_id": "uuid-plan",
    "industry": {
      "name": "Information Technology"
    },
    "plan": {
      "name": "Professional Employer Plan",
      "price": "49.99"
    },
    "total_jobs_posted": 15,
    "active_jobs": 10
  }
}
```

### Update Employer

```http
PUT /api/v1/admin/employers/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "company_name": "Tech Innovations Corp",
  "email": "hr@techinnovationscorp.com",
  "contact": "+1987654321",
  "industry_type": "uuid-new-industry"
}
```

**Response:**
```json
{
  "message": "Employer updated successfully"
}
```

### Delete Employer

```http
DELETE /api/v1/admin/employers/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Employer deleted successfully"
}
```

---

## Job Management

**Auth Required:** Admin

### List All Jobs

```http
GET /api/v1/admin/jobs?page=1&status=active&search=developer
Authorization: Bearer {token}
```

**Query Parameters:**
- `page`: Page number
- `status`: active, inactive, expired
- `search`: Search by title
- `employer_id`: Filter by employer
- `category_id`: Filter by category
- `location_id`: Filter by location

**Response:**
```json
{
  "jobs": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "title": "Senior Software Developer",
        "description": "Description...",
        "salary": "$80,000 - $120,000",
        "status": "active",
        "is_featured": true,
        "employer": {
          "company_name": "Tech Corp"
        },
        "location": {
          "name": "New York"
        },
        "category": {
          "name": "Software Development"
        },
        "applications_count": 25,
        "created_at": "2024-10-01T10:00:00.000000Z"
      }
    ],
    "total": 890,
    "per_page": 20,
    "last_page": 45
  }
}
```

---

## Coupon Management

**Auth Required:** Super Admin Only

### List Coupons

```http
GET /api/v1/admin/coupons
Authorization: Bearer {token}
```

**Response:**
```json
{
  "coupons": [
    {
      "id": "uuid-here",
      "code": "SAVE20",
      "discount_percentage": "20.00",
      "max_uses": 100,
      "times_used": 45,
      "expiry_date": "2025-12-31",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Create Coupon

```http
POST /api/v1/admin/coupons
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "NEWYEAR25",
  "discount_percentage": 25.00,
  "max_uses": 200,
  "expiry_date": "2025-12-31",
  "is_active": true
}
```

**Response (201 Created):**
```json
{
  "message": "Coupon created successfully",
  "coupon": {
    "id": "uuid-here",
    "code": "NEWYEAR25",
    "discount_percentage": "25.00",
    "max_uses": 200,
    "expiry_date": "2025-12-31"
  }
}
```

### Update Coupon

```http
PUT /api/v1/admin/coupons/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "is_active": false
}
```

### Delete Coupon

```http
DELETE /api/v1/admin/coupons/{id}
Authorization: Bearer {token}
```

---

## Commission Management

### Get All Commissions

**Auth Required:** Super Admin Only

```http
GET /api/v1/admin/commissions/all?page=1
Authorization: Bearer {token}
```

**Response:**
```json
{
  "commissions": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "admin_id": "uuid-admin",
        "admin_name": "Jane Manager",
        "amount": "50.00",
        "type": "subscription",
        "reference_id": "uuid-payment",
        "created_at": "2024-10-01T10:00:00.000000Z"
      }
    ],
    "total": 350,
    "per_page": 20,
    "last_page": 18
  },
  "total_commissions": "15750.50"
}
```

### Get My Commissions

**Auth Required:** Staff Admin

```http
GET /api/v1/admin/commissions/my
Authorization: Bearer {token}
```

**Response:**
```json
{
  "commissions": [
    {
      "id": "uuid-here",
      "amount": "50.00",
      "type": "subscription",
      "reference_id": "uuid-payment",
      "created_at": "2024-10-01T10:00:00.000000Z"
    }
  ],
  "my_total_commissions": "850.00"
}
```

### Add Manual Commission

**Auth Required:** Admin

```http
POST /api/v1/admin/commissions/manual
Authorization: Bearer {token}
Content-Type: application/json

{
  "admin_id": "uuid-of-admin",
  "amount": 100.00,
  "type": "manual_bonus",
  "description": "Q4 Performance Bonus"
}
```

**Response (201 Created):**
```json
{
  "message": "Manual commission added successfully",
  "commission": {
    "id": "uuid-here",
    "admin_id": "uuid-admin",
    "amount": "100.00",
    "type": "manual_bonus"
  }
}
```

---

## CV Request Management

**Auth Required:** Admin

### List CV Requests

```http
GET /api/v1/admin/cv-requests?page=1&status=pending
Authorization: Bearer {token}
```

**Query Parameters:**
- `status`: pending, in_progress, completed, rejected

**Response:**
```json
{
  "cv_requests": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "employee_id": "uuid-employee",
        "employee": {
          "name": "John Doe",
          "email": "john.doe@example.com"
        },
        "notes": "Need modern tech CV",
        "preferred_template": "Modern Tech",
        "status": "pending",
        "completed_cv_url": null,
        "created_at": "2024-10-05T10:00:00.000000Z"
      }
    ],
    "total": 23,
    "per_page": 20,
    "last_page": 2
  }
}
```

### Update CV Request Status

```http
PUT /api/v1/admin/cv-requests/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "in_progress"
}
```

**Valid Status:** `pending`, `in_progress`, `completed`, `rejected`

**For Completed Status:**
```http
PUT /api/v1/admin/cv-requests/{id}/status
Authorization: Bearer {token}
Content-Type: multipart/form-data

status: completed
cv_file: [File] (PDF)
```

**Response:**
```json
{
  "message": "CV request status updated successfully",
  "completed_cv_url": "/storage/professional_cvs/cv_123.pdf"
}
```

---

## Plan Management

**Auth Required:** Admin (plan_upgrade_manager or super_admin)

### Create Plan

```http
POST /api/v1/plans
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Enterprise Employer Plan",
  "description": "Unlimited job posts",
  "type": "employer",
  "price": 199.99,
  "validity_days": 30
}
```

**Valid Types:** `employee`, `employer`

**Response (201 Created):**
```json
{
  "message": "Plan created successfully",
  "plan": {
    "id": "uuid-here",
    "name": "Enterprise Employer Plan",
    "type": "employer",
    "price": "199.99",
    "validity_days": 30
  }
}
```

### Update Plan

```http
PUT /api/v1/plans/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Enterprise Employer Plan Updated",
  "price": 249.99
}
```

### Delete Plan

```http
DELETE /api/v1/plans/{id}
Authorization: Bearer {token}
```

### Add Plan Feature

```http
POST /api/v1/plans/{planId}/features
Authorization: Bearer {token}
Content-Type: application/json

{
  "feature_name": "featured_jobs",
  "feature_value": "10"
}
```

**Common Features:**
- `num_job_applies`: Number of job applications (employee)
- `num_job_posts`: Number of job posts (employer)
- `featured_jobs`: Number of featured jobs (employer)
- `cv_generation`: CV generation allowed (employee)
- `professional_cv`: Professional CV service (employee)

**Response (201 Created):**
```json
{
  "message": "Feature added successfully",
  "feature": {
    "id": "uuid-here",
    "plan_id": "uuid-plan",
    "feature_name": "featured_jobs",
    "feature_value": "10"
  }
}
```

### Remove Plan Feature

```http
DELETE /api/v1/plans/features/{featureId}
Authorization: Bearer {token}
```

---

## Catalog Management

**Auth Required:** Admin (catalog_manager or super_admin)

### Industry Management

**Create Industry:**
```http
POST /api/v1/catalogs/industries
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Healthcare"
}
```

**Update Industry:**
```http
PUT /api/v1/catalogs/industries/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Healthcare & Medical"
}
```

**Delete Industry:**
```http
DELETE /api/v1/catalogs/industries/{id}
Authorization: Bearer {token}
```

---

### Location Management

**Create Location:**
```http
POST /api/v1/catalogs/locations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Los Angeles",
  "state": "CA",
  "country": "USA"
}
```

**Update Location:**
```http
PUT /api/v1/catalogs/locations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Los Angeles",
  "state": "California",
  "country": "United States"
}
```

**Delete Location:**
```http
DELETE /api/v1/catalogs/locations/{id}
Authorization: Bearer {token}
```

---

### Job Category Management

**Create Category:**
```http
POST /api/v1/catalogs/categories
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Data Science"
}
```

**Update Category:**
```http
PUT /api/v1/catalogs/categories/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Data Science & Analytics"
}
```

**Delete Category:**
```http
DELETE /api/v1/catalogs/categories/{id}
Authorization: Bearer {token}
```

---

## Error Handling

### Standard Error Format

```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Specific validation error"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (resource created) |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Authentication required/failed |
| 403 | Forbidden | Not authorized for this action |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

### Common Admin Errors

**403 Forbidden - Insufficient Permissions:**
```json
{
  "message": "This action is unauthorized. Required role: super_admin"
}
```

**403 Forbidden - Cannot Delete Self:**
```json
{
  "message": "You cannot delete your own account"
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "role": ["The selected role is invalid."]
  }
}
```

---

## Integration Best Practices

### 1. Role-Based UI Components

```javascript
const adminRole = localStorage.getItem('admin_role');

// Show/hide components based on role
const canManageAdmins = adminRole === 'super_admin';
const canManageCoupons = adminRole === 'super_admin';
const canManageEmployees = ['super_admin', 'employee_manager'].includes(adminRole);
```

### 2. API Error Handling

```javascript
const handleAdminResponse = async (response) => {
  if (!response.ok) {
    const error = await response.json();

    switch (response.status) {
      case 401:
        // Redirect to admin login
        window.location.href = '/admin/login';
        break;
      case 403:
        showMessage('You do not have permission for this action');
        break;
      case 422:
        displayErrors(error.errors);
        break;
      default:
        showMessage(error.message || 'An error occurred');
    }
    throw error;
  }
  return response.json();
};
```

### 3. Axios Configuration for Admin

```javascript
import axios from 'axios';

const adminApi = axios.create({
  baseURL: 'http://localhost:8000/api/v1/admin',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

adminApi.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

adminApi.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.location.href = '/admin/login';
    } else if (error.response?.status === 403) {
      alert('You do not have permission for this action');
    }
    return Promise.reject(error);
  }
);

export default adminApi;
```

---

## Testing with cURL

### Admin Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"admin@jobportal.com","password":"password123"}'
```

### Get Dashboard Stats
```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Create Admin (Super Admin)
```bash
curl -X POST http://localhost:8000/api/v1/admin/admins \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Admin",
    "email": "newadmin@jobportal.com",
    "password": "SecurePass123!",
    "role": "employee_manager"
  }'
```

---

## Additional Notes

### Date Format
All dates use ISO 8601: `YYYY-MM-DDTHH:MM:SS.000000Z`

### UUID Format
All IDs are UUIDs: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

### Pagination
- Default: 20 items per page
- Query parameter: `?page=N`
- Response includes: `current_page`, `last_page`, `total`, `data`

### Rate Limiting
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`
- Status: 429 Too Many Requests

---

## Support & Feedback

For questions or issues, please contact the backend development team.

**API Status:** Production Ready ✅
**Version:** 1.0
**Last Updated:** October 6, 2025

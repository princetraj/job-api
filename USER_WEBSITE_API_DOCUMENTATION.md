# Job Portal - User Website API Documentation

**Version:** 1.0
**Last Updated:** October 6, 2025
**Status:** Production Ready ✅
**Base URL:** `http://localhost:8000/api/v1`

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Authentication Flow](#authentication-flow)
3. [Public Endpoints](#public-endpoints)
4. [Employee Endpoints](#employee-endpoints)
5. [Employer Endpoints](#employer-endpoints)
6. [Payment & Subscription](#payment--subscription)
7. [Error Handling](#error-handling)
8. [Integration Best Practices](#integration-best-practices)

---

## Quick Start

### Authentication Setup

```javascript
// Store token after login/registration
localStorage.setItem('auth_token', response.token);
localStorage.setItem('user_type', response.user_type); // employee or employer

// Include in all authenticated requests
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};
```

### Making API Calls

```javascript
// Example: Get employee profile
const response = await fetch('http://localhost:8000/api/v1/employee/profile', {
  method: 'GET',
  headers: headers
});

if (response.ok) {
  const data = await response.json();
  console.log(data);
} else if (response.status === 401) {
  // Redirect to login
} else if (response.status === 422) {
  // Show validation errors
  const errors = await response.json();
  console.log(errors.errors);
}
```

---

## Authentication Flow

### Complete User Journeys

#### Employee Registration (3-Step Process)

**Step 1: Basic Information**
```http
POST /api/v1/auth/register/employee-step1
Content-Type: application/json

{
  "email": "john.doe@example.com",
  "mobile": "+1234567890",
  "name": "John Doe",
  "password": "SecurePass123!",
  "gender": "M"
}
```

**Response:**
```json
{
  "message": "Step 1 complete.",
  "tempToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Step 2: Personal Details**
```http
POST /api/v1/auth/register/employee-step2
Authorization: Bearer {tempToken}
Content-Type: application/json

{
  "dob": "1990-05-15",
  "address": {
    "street": "123 Main Street",
    "city": "New York",
    "state": "NY",
    "zip": "10001",
    "country": "USA"
  }
}
```

**Response:**
```json
{
  "message": "Step 2 complete."
}
```

**Step 3: Professional Information**
```http
POST /api/v1/auth/register/employee-final
Authorization: Bearer {tempToken}
Content-Type: application/json

{
  "education": [
    {
      "degree": "Bachelor of Science",
      "university": "MIT",
      "year_start": "2010",
      "year_end": "2014",
      "field": "Computer Science"
    }
  ],
  "experience": [
    {
      "company": "Tech Corp",
      "title": "Software Engineer",
      "year_start": "2014",
      "year_end": "2020",
      "description": "Developed web applications"
    }
  ],
  "skills": ["JavaScript", "React", "Node.js", "Python"]
}
```

**Response:**
```json
{
  "message": "Registration complete.",
  "token": "1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

#### Employer Registration (Single Step)

```http
POST /api/v1/auth/register/employer
Content-Type: application/json

{
  "company_name": "Tech Innovations Inc.",
  "email": "hr@techinnovations.com",
  "contact": "+1234567890",
  "password": "SecurePass123!",
  "address": {
    "street": "456 Business Ave",
    "city": "San Francisco",
    "state": "CA",
    "zip": "94105",
    "country": "USA"
  },
  "industry_type_id": "uuid-of-industry"
}
```

**Response (201 Created):**
```json
{
  "message": "Registration complete.",
  "token": "2|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

#### Login (Employee & Employer)

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "identifier": "john.doe@example.com",
  "password": "SecurePass123!"
}
```

**Note:** `identifier` can be email or mobile number.

**Employee Response:**
```json
{
  "token": "3|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user_type": "employee",
  "user": {
    "id": "uuid-here",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "mobile": "+1234567890"
  }
}
```

**Employer Response:**
```json
{
  "token": "4|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user_type": "employer",
  "user": {
    "id": "uuid-here",
    "company_name": "Tech Corp",
    "email": "hr@techcorp.com",
    "contact": "+1234567890"
  }
}
```

---

#### Logout

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

## Public Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/plans` | GET | Get all subscription plans |
| `/plans/{id}` | GET | Get specific plan details |
| `/catalogs/industries` | GET | List all industries |
| `/catalogs/locations` | GET | List all locations |
| `/catalogs/categories` | GET | List all job categories |
| `/jobs/search` | GET | Public job search |
| `/content` | GET | Get public content list |
| `/content/{identifier}` | GET | Get content by ID or slug |
| `/media/{id}` | GET | Get media file |
| `/coupons/validate` | POST | Validate coupon code |

---

## Employee Endpoints

### Profile Management

**Get Profile:**
```http
GET /api/v1/employee/profile
Authorization: Bearer {token}
```

**Response:**
```json
{
  "user": {
    "id": "uuid-here",
    "email": "john.doe@example.com",
    "mobile": "+1234567890",
    "name": "John Doe",
    "gender": "M",
    "dob": "1990-05-15",
    "address": {
      "street": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "zip": "10001",
      "country": "USA"
    },
    "education_details": [...],
    "experience_details": [...],
    "skills_details": [...],
    "cv_url": "/storage/cvs/cv_123.pdf",
    "plan_id": "uuid-of-plan",
    "created_at": "2024-01-15T10:30:00.000000Z"
  },
  "plan": {
    "id": "uuid-here",
    "name": "Basic Employee Plan",
    "price": "9.99",
    "features": [...]
  }
}
```

**Update Profile:**
```http
PUT /api/v1/employee/profile/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "field": "address",
  "value": {
    "street": "789 New Street",
    "city": "Boston",
    "state": "MA",
    "zip": "02101",
    "country": "USA"
  }
}
```

**Allowed Fields:** `address`, `education_details`, `experience_details`, `skills_details`, `cv_url`

**Response:**
```json
{
  "message": "Profile updated."
}
```

---

### Job Search & Application

**Search Jobs:**
```http
GET /api/v1/employee/jobs/search?q=developer&location_id={uuid}&category_id={uuid}&page=1
Authorization: Bearer {token}
```

**Response:**
```json
{
  "jobs": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "title": "Senior Software Developer",
        "description": "We are looking for an experienced developer...",
        "salary": "$80,000 - $120,000",
        "location_id": "uuid-of-location",
        "category_id": "uuid-of-category",
        "is_featured": true,
        "employer": {
          "id": "uuid-here",
          "company_name": "Tech Corp"
        },
        "location": {
          "name": "New York",
          "state": "NY"
        },
        "category": {
          "name": "Software Development"
        }
      }
    ],
    "total": 50,
    "per_page": 20,
    "last_page": 3
  }
}
```

**Apply for Job:**
```http
POST /api/v1/employee/jobs/{jobId}/apply
Authorization: Bearer {token}
```

**Response (201 Created):**
```json
{
  "message": "Application submitted."
}
```

**Errors:**
- 400: Already applied
- 404: Job not found

**Get Applied Jobs:**
```http
GET /api/v1/employee/jobs/applied
Authorization: Bearer {token}
```

**Response:**
```json
{
  "jobs": [
    {
      "id": "uuid-job-id",
      "title": "Senior Software Developer",
      "status": "applied",
      "employer": {
        "company_name": "Tech Corp"
      },
      "applied_at": "2024-10-05T14:30:00.000000Z"
    }
  ]
}
```

**Status Values:** `applied`, `shortlisted`, `interview_scheduled`, `selected`, `rejected`

---

### Job Shortlist Management

**Add to Shortlist:**
```http
POST /api/v1/employee/jobs/shortlist
Authorization: Bearer {token}
Content-Type: application/json

{
  "job_id": "uuid-of-job"
}
```

**Response (201 Created):**
```json
{
  "message": "Job shortlisted."
}
```

**Get Shortlisted Jobs:**
```http
GET /api/v1/employee/jobs/shortlisted
Authorization: Bearer {token}
```

**Response:**
```json
{
  "jobs": [
    {
      "id": "uuid-here",
      "title": "Senior Software Developer",
      "employer": { "company_name": "Tech Corp" },
      "location": { "name": "New York" },
      "category": { "name": "Software Development" }
    }
  ]
}
```

**Remove from Shortlist:**
```http
DELETE /api/v1/employee/jobs/shortlist/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Job removed from shortlist."
}
```

---

### CV Management

**Generate CV:**
```http
GET /api/v1/employee/cv/generate
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "CV generated successfully",
  "cv_data": {
    "name": "John Doe",
    "email": "john.doe@example.com",
    "education": [...],
    "experience": [...],
    "skills": [...]
  },
  "download_url": "/api/v1/employee/cv/download/uuid"
}
```

**Upload CV:**
```http
POST /api/v1/employee/cv/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

cv_file: [File] (PDF, DOC, DOCX - Max 5MB)
```

**Response:**
```json
{
  "message": "CV uploaded successfully",
  "cv_url": "/storage/cvs/cv_123_1696598400.pdf"
}
```

**Request Professional CV:**
```http
POST /api/v1/employee/cv/request-professional
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "I need a modern CV template focused on tech skills",
  "preferred_template": "Modern Tech"
}
```

**Response (201 Created):**
```json
{
  "message": "Professional CV request submitted",
  "request_id": "uuid-here",
  "status": "pending",
  "estimated_delivery": "2024-10-09"
}
```

**Get CV Requests:**
```http
GET /api/v1/employee/cv/requests
Authorization: Bearer {token}
```

**Response:**
```json
{
  "requests": [
    {
      "id": "uuid-here",
      "notes": "Modern tech CV needed",
      "preferred_template": "Modern Tech",
      "status": "completed",
      "completed_cv_url": "/storage/professional_cvs/cv_123.pdf",
      "created_at": "2024-10-01T10:00:00.000000Z"
    }
  ]
}
```

**CV Request Status:** `pending`, `in_progress`, `completed`, `rejected`

---

## Employer Endpoints

### Profile Management

**Get Profile:**
```http
GET /api/v1/employer/profile
Authorization: Bearer {token}
```

**Response:**
```json
{
  "user": {
    "id": "uuid-here",
    "company_name": "Tech Innovations Inc.",
    "email": "hr@techinnovations.com",
    "contact": "+1234567890",
    "address": {...},
    "industry_type": "uuid-of-industry",
    "plan_id": "uuid-of-plan",
    "industry": {
      "name": "Information Technology"
    }
  },
  "plan": {
    "name": "Professional Employer Plan",
    "price": "49.99",
    "features": [...]
  }
}
```

**Update Profile:**
```http
PUT /api/v1/employer/profile/update
Authorization: Bearer {token}
Content-Type: application/json

{
  "company_name": "Tech Innovations Corp",
  "contact": "+1987654321",
  "address": {...},
  "industry_type": "uuid-of-new-industry"
}
```

---

### Job Management

**Create Job:**
```http
POST /api/v1/employer/jobs
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Senior Full Stack Developer",
  "description": "We are seeking an experienced full stack developer...",
  "salary": "$90,000 - $130,000",
  "location_id": "uuid-of-location",
  "category_id": "uuid-of-category"
}
```

**Response (201 Created):**
```json
{
  "job_id": "uuid-of-new-job",
  "message": "Job created."
}
```

**Get Job Details:**
```http
GET /api/v1/employer/jobs/{jobId}
Authorization: Bearer {token}
```

**Update Job:**
```http
PUT /api/v1/employer/jobs/{jobId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Senior Full Stack Developer (Updated)",
  "description": "Updated description...",
  "salary": "$95,000 - $135,000"
}
```

**Delete Job:**
```http
DELETE /api/v1/employer/jobs/{jobId}
Authorization: Bearer {token}
```

**Get Job Applications:**
```http
GET /api/v1/employer/jobs/{jobId}/applications
Authorization: Bearer {token}
```

**Response:**
```json
{
  "applications": [
    {
      "id": "uuid-application",
      "employee": {
        "id": "uuid-employee",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "cv_url": "/storage/cvs/cv_123.pdf",
        "education_details": [...],
        "experience_details": [...],
        "skills_details": [...]
      },
      "applied_at": "2024-10-05T14:30:00.000000Z",
      "status": "applied"
    }
  ]
}
```

**Update Application Status:**
```http
PUT /api/v1/employer/applications/{appId}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "shortlisted"
}
```

**Valid Status:** `applied`, `shortlisted`, `interview_scheduled`, `selected`, `rejected`

**Response:**
```json
{
  "message": "Status updated.",
  "whatsapp_sent": true
}
```

---

## Payment & Subscription

### Subscribe to Plan

```http
POST /api/v1/payments/subscribe
Authorization: Bearer {token}
Content-Type: application/json

{
  "plan_id": "uuid-of-plan",
  "coupon_code": "SAVE20",
  "payment_method": "stripe",
  "payment_details": {
    "card_number": "4242424242424242",
    "exp_month": "12",
    "exp_year": "2025",
    "cvc": "123"
  }
}
```

**Note:** `coupon_code` is optional

**Response (201 Created):**
```json
{
  "message": "Subscription successful",
  "payment": {
    "id": "uuid-payment",
    "user_type": "employee",
    "user_id": "uuid-user",
    "plan_id": "uuid-plan",
    "amount": "9.99",
    "discount_amount": "2.00",
    "final_amount": "7.99",
    "coupon_code": "SAVE20",
    "payment_status": "completed",
    "transaction_id": "txn_123456789"
  },
  "subscription_expires_at": "2024-11-06T15:30:00.000000Z"
}
```

### Verify Payment

```http
POST /api/v1/payments/verify
Authorization: Bearer {token}
Content-Type: application/json

{
  "payment_id": "uuid-of-payment",
  "transaction_id": "txn_123456789"
}
```

**Response:**
```json
{
  "message": "Payment verified",
  "payment": {
    "id": "uuid-payment",
    "payment_status": "completed",
    "amount": "7.99"
  }
}
```

### Get Payment History

```http
GET /api/v1/payments/history
Authorization: Bearer {token}
```

### Validate Coupon

```http
POST /api/v1/coupons/validate
Content-Type: application/json

{
  "coupon_code": "SAVE20",
  "plan_id": "uuid-of-plan"
}
```

**Note:** Field is `coupon_code`, not `code`.

**Valid Coupon Response:**
```json
{
  "valid": true,
  "coupon": {
    "code": "SAVE20",
    "discount_percentage": "20.00",
    "expiry_date": "2025-12-31"
  },
  "plan": {
    "price": "9.99"
  },
  "discount_amount": "2.00",
  "final_amount": "7.99"
}
```

**Invalid Coupon Response (200 OK):**
```json
{
  "valid": false,
  "message": "Invalid or expired coupon code"
}
```

---

## Catalog APIs

**Get Industries:**
```http
GET /api/v1/catalogs/industries
```

**Response:**
```json
{
  "industries": [
    {
      "id": "uuid-here",
      "name": "Information Technology"
    }
  ]
}
```

**Get Locations:**
```http
GET /api/v1/catalogs/locations
```

**Response:**
```json
{
  "locations": [
    {
      "id": "uuid-here",
      "name": "New York",
      "state": "NY",
      "country": "USA"
    }
  ]
}
```

**Get Job Categories:**
```http
GET /api/v1/catalogs/categories
```

**Response:**
```json
{
  "categories": [
    {
      "id": "uuid-here",
      "name": "Software Development"
    }
  ]
}
```

---

## Plan Management

**Get All Plans:**
```http
GET /api/v1/plans?type=employee
```

**Response:**
```json
{
  "plans": [
    {
      "id": "uuid-here",
      "name": "Basic Employee Plan",
      "description": "Perfect for job seekers starting out",
      "type": "employee",
      "price": "9.99",
      "validity_days": 30,
      "features": [
        {
          "feature_name": "num_job_applies",
          "feature_value": "5"
        }
      ]
    }
  ]
}
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
| 403 | Forbidden | Not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

### Common Errors

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Include valid Bearer token

**403 Forbidden:**
```json
{
  "message": "This action is unauthorized."
}
```
**Solution:** User lacks permission

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```
**Solution:** Fix validation errors and retry

---

## Integration Best Practices

### 1. Token Management

```javascript
// Store after login/registration
localStorage.setItem('auth_token', response.token);
localStorage.setItem('user_type', response.user_type);

// Clear on logout
const logout = async () => {
  await fetch('/api/v1/auth/logout', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
  });
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user_type');
};
```

### 2. Error Handling

```javascript
const handleResponse = async (response) => {
  if (!response.ok) {
    const error = await response.json();

    switch (response.status) {
      case 401:
        // Redirect to login
        window.location.href = '/login';
        break;
      case 422:
        // Show validation errors
        displayErrors(error.errors);
        break;
      case 403:
        // Show unauthorized message
        showMessage('You do not have permission');
        break;
      default:
        showMessage(error.message || 'An error occurred');
    }
    throw error;
  }
  return response.json();
};
```

### 3. File Upload

```javascript
const uploadCV = async (file) => {
  const formData = new FormData();
  formData.append('cv_file', file);

  const response = await fetch('/api/v1/employee/cv/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
      // Don't set Content-Type for FormData
    },
    body: formData
  });

  return handleResponse(response);
};
```

### 4. Pagination

```javascript
const loadJobs = async (page = 1) => {
  const response = await fetch(
    `/api/v1/employee/jobs/search?page=${page}&q=developer`,
    { headers }
  );
  const data = await response.json();

  return {
    jobs: data.jobs.data,
    currentPage: data.jobs.current_page,
    lastPage: data.jobs.last_page,
    total: data.jobs.total
  };
};
```

### 5. Search with Query Parameters

```javascript
const searchJobs = async (filters) => {
  const params = new URLSearchParams();
  if (filters.query) params.append('q', filters.query);
  if (filters.location) params.append('location_id', filters.location);
  if (filters.category) params.append('category_id', filters.category);
  if (filters.page) params.append('page', filters.page);

  const response = await fetch(
    `/api/v1/employee/jobs/search?${params.toString()}`,
    { headers }
  );
  return handleResponse(response);
};
```

### 6. Real-time Updates (Polling)

```javascript
// Poll for application status updates every 30 seconds
const pollApplicationStatus = () => {
  setInterval(async () => {
    const response = await fetch('/api/v1/employee/jobs/applied', {
      headers
    });
    const data = await response.json();
    updateApplicationStatus(data.jobs);
  }, 30000);
};
```

### 7. Axios Configuration (Alternative)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add token to all requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors globally
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"user@example.com","password":"password123"}'
```

### Get Profile (with token)
```bash
curl -X GET http://localhost:8000/api/v1/employee/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Create Job
```bash
curl -X POST http://localhost:8000/api/v1/employer/jobs \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Software Developer",
    "description": "Looking for developer",
    "salary": "$80,000",
    "location_id": "uuid",
    "category_id": "uuid"
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

### WhatsApp Notifications
- Triggered server-side for application status changes
- No frontend action required
- Employees receive notifications automatically

---

## Support & Feedback

For questions or issues, please contact the backend development team.

**API Status:** Production Ready ✅
**Version:** 1.0
**Last Updated:** October 6, 2025

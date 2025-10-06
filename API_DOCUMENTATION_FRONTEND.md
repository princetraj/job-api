# Job Portal API - Complete Frontend Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Base URL & Authentication](#base-url--authentication)
3. [Authentication APIs](#authentication-apis)
4. [Employee APIs](#employee-apis)
5. [Employer APIs](#employer-apis)
6. [Admin APIs](#admin-apis)
7. [Plan APIs](#plan-apis)
8. [Catalog APIs](#catalog-apis)
9. [Payment APIs](#payment-apis)
10. [Public APIs](#public-apis)
11. [Error Handling](#error-handling)

---

## Introduction

This document provides complete API documentation for frontend developers to integrate with the Job Portal backend. All endpoints include request/response payloads, status codes, and example usage.

**API Version:** v1
**Last Updated:** 2025-10-06

---

## Base URL & Authentication

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
The API uses **Laravel Sanctum** token-based authentication.

**Headers Required:**
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token}"
}
```

**Token Storage:**
- Store the token received after login/registration
- Include in all authenticated requests
- Token format: `Bearer {your-token-here}`

---

# Authentication APIs

## 1. Employee Registration - Step 1

**Endpoint:** `POST /api/v1/auth/register/employee-step1`
**Auth Required:** No

### Request Payload
```json
{
  "email": "john.doe@example.com",
  "mobile": "+1234567890",
  "name": "John Doe",
  "password": "SecurePass123!",
  "gender": "M"
}
```

### Field Validation
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| email | string | Yes | Valid email, unique |
| mobile | string | Yes | Valid phone, unique |
| name | string | Yes | Min: 2 chars |
| password | string | Yes | Min: 8 chars |
| gender | string | Yes | Enum: M, F, O |

### Response (200 OK)
```json
{
  "message": "Step 1 complete.",
  "tempToken": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Error Response (422 Unprocessable Entity)
```json
{
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## 2. Employee Registration - Step 2

**Endpoint:** `POST /api/v1/auth/register/employee-step2`
**Auth Required:** Yes (tempToken from Step 1)

### Request Payload
```json
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

### Field Validation
| Field | Type | Required | Rules |
|-------|------|----------|-------|
| dob | string (date) | Yes | Format: YYYY-MM-DD |
| address | object | Yes | JSON object |

### Response (200 OK)
```json
{
  "message": "Step 2 complete."
}
```

---

## 3. Employee Registration - Final Step

**Endpoint:** `POST /api/v1/auth/register/employee-final`
**Auth Required:** Yes (tempToken)

### Request Payload
```json
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
  "skills": [
    "JavaScript",
    "React",
    "Node.js",
    "Python"
  ]
}
```

### Response (200 OK)
```json
{
  "message": "Registration complete.",
  "token": "1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

## 4. Employer Registration

**Endpoint:** `POST /api/v1/auth/register/employer`
**Auth Required:** No

### Request Payload
```json
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

### Response (201 Created)
```json
{
  "message": "Registration complete.",
  "token": "2|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

---

## 5. Login

**Endpoint:** `POST /api/v1/auth/login`
**Auth Required:** No

### Request Payload
```json
{
  "identifier": "john.doe@example.com",
  "password": "SecurePass123!"
}
```

**Note:** `identifier` can be email or mobile number.

### Response (200 OK)

**For Employee:**
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

**For Employer:**
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

**For Admin:**
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

### Error Response (401 Unauthorized)
```json
{
  "message": "Invalid credentials"
}
```

---

## 6. Logout

**Endpoint:** `POST /api/v1/auth/logout`
**Auth Required:** Yes

### Request Payload
```json
{}
```

### Response (200 OK)
```json
{
  "message": "Logged out successfully"
}
```

---

# Employee APIs

## 1. Get Employee Profile

**Endpoint:** `GET /api/v1/employee/profile`
**Auth Required:** Yes (Employee)

### Response (200 OK)
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
    "description": "5 job applications per month",
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
}
```

---

## 2. Update Employee Profile

**Endpoint:** `PUT /api/v1/employee/profile/update`
**Auth Required:** Yes (Employee)

### Request Payload
```json
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

**Allowed Fields:**
- `address`
- `education_details`
- `experience_details`
- `skills_details`
- `cv_url`

### Response (200 OK)
```json
{
  "message": "Profile updated."
}
```

---

## 3. Search Jobs

**Endpoint:** `GET /api/v1/employee/jobs/search`
**Auth Required:** Yes (Employee) / Public endpoint also available

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | No | Search by job title or description |
| location_id | uuid | No | Filter by location |
| category_id | uuid | No | Filter by category |
| page | integer | No | Pagination page number |

### Example Request
```
GET /api/v1/employee/jobs/search?q=developer&location_id=uuid&page=1
```

### Response (200 OK)
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
        "featured_end_date": "2025-12-31T23:59:59.000000Z",
        "created_at": "2024-10-01T10:00:00.000000Z",
        "employer": {
          "id": "uuid-here",
          "company_name": "Tech Corp",
          "email": "hr@techcorp.com"
        },
        "location": {
          "id": "uuid-here",
          "name": "New York",
          "state": "NY"
        },
        "category": {
          "id": "uuid-here",
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

---

## 4. Apply for Job

**Endpoint:** `POST /api/v1/employee/jobs/{jobId}/apply`
**Auth Required:** Yes (Employee)

### URL Parameters
| Parameter | Description |
|-----------|-------------|
| jobId | UUID of the job to apply for |

### Request Payload
```json
{}
```

### Response (201 Created)
```json
{
  "message": "Application submitted."
}
```

### Error Responses
**400 Bad Request** - Already applied
```json
{
  "message": "Already applied to this job"
}
```

**404 Not Found** - Job doesn't exist
```json
{
  "message": "Job not found"
}
```

---

## 5. Get Applied Jobs

**Endpoint:** `GET /api/v1/employee/jobs/applied`
**Auth Required:** Yes (Employee)

### Response (200 OK)
```json
{
  "jobs": [
    {
      "id": "uuid-job-id",
      "title": "Senior Software Developer",
      "status": "applied",
      "employer": {
        "id": "uuid-employer-id",
        "company_name": "Tech Corp",
        "email": "hr@techcorp.com"
      },
      "applied_at": "2024-10-05T14:30:00.000000Z"
    },
    {
      "id": "uuid-job-id-2",
      "title": "Frontend Developer",
      "status": "shortlisted",
      "employer": {
        "id": "uuid-employer-id-2",
        "company_name": "Web Agency"
      },
      "applied_at": "2024-10-03T09:15:00.000000Z"
    }
  ]
}
```

**Application Status Values:**
- `applied` - Application submitted
- `shortlisted` - Shortlisted by employer
- `interview_scheduled` - Interview scheduled
- `selected` - Selected for position
- `rejected` - Application rejected

---

## 6. Shortlist Job

**Endpoint:** `POST /api/v1/employee/jobs/shortlist`
**Auth Required:** Yes (Employee)

### Request Payload
```json
{
  "job_id": "uuid-of-job"
}
```

### Response (201 Created)
```json
{
  "message": "Job shortlisted."
}
```

### Error Response (400 Bad Request)
```json
{
  "message": "Job already shortlisted"
}
```

---

## 7. Get Shortlisted Jobs

**Endpoint:** `GET /api/v1/employee/jobs/shortlisted`
**Auth Required:** Yes (Employee)

### Response (200 OK)
```json
{
  "jobs": [
    {
      "id": "uuid-here",
      "title": "Senior Software Developer",
      "description": "Looking for experienced developer...",
      "salary": "$80,000 - $120,000",
      "employer": {
        "company_name": "Tech Corp"
      },
      "location": {
        "name": "New York"
      },
      "category": {
        "name": "Software Development"
      }
    }
  ]
}
```

---

## 8. Remove Job from Shortlist

**Endpoint:** `DELETE /api/v1/employee/jobs/shortlist/{id}`
**Auth Required:** Yes (Employee)

### URL Parameters
| Parameter | Description |
|-----------|-------------|
| id | UUID of the shortlisted job entry |

### Response (200 OK)
```json
{
  "message": "Job removed from shortlist."
}
```

---

## 9. Generate CV

**Endpoint:** `GET /api/v1/employee/cv/generate`
**Auth Required:** Yes (Employee)

### Response (200 OK)
```json
{
  "message": "CV generated successfully",
  "cv_data": {
    "name": "John Doe",
    "email": "john.doe@example.com",
    "mobile": "+1234567890",
    "gender": "M",
    "dob": "1990-05-15",
    "address": {...},
    "education": [...],
    "experience": [...],
    "skills": [...],
    "generated_at": "2024-10-06T15:30:00.000000Z"
  },
  "download_url": "/api/v1/employee/cv/download/uuid"
}
```

---

## 10. Upload CV

**Endpoint:** `POST /api/v1/employee/cv/upload`
**Auth Required:** Yes (Employee)

### Request (Multipart Form Data)
```
Content-Type: multipart/form-data

cv_file: [File] (PDF, DOC, DOCX - Max 5MB)
```

### Response (200 OK)
```json
{
  "message": "CV uploaded successfully",
  "cv_url": "/storage/cvs/cv_123_1696598400.pdf"
}
```

### Error Response (422 Unprocessable Entity)
```json
{
  "errors": {
    "cv_file": [
      "The cv file must be a file of type: pdf, doc, docx."
    ]
  }
}
```

---

## 11. Request Professional CV

**Endpoint:** `POST /api/v1/employee/cv/request-professional`
**Auth Required:** Yes (Employee)

### Request Payload
```json
{
  "notes": "I need a modern CV template focused on tech skills",
  "preferred_template": "Modern Tech"
}
```

### Response (201 Created)
```json
{
  "message": "Professional CV request submitted",
  "request_id": "uuid-here",
  "status": "pending",
  "estimated_delivery": "2024-10-09"
}
```

---

## 12. Get CV Requests

**Endpoint:** `GET /api/v1/employee/cv/requests`
**Auth Required:** Yes (Employee)

### Response (200 OK)
```json
{
  "requests": [
    {
      "id": "uuid-here",
      "employee_id": "uuid-employee",
      "notes": "Modern tech CV needed",
      "preferred_template": "Modern Tech",
      "status": "completed",
      "completed_cv_url": "/storage/professional_cvs/cv_123.pdf",
      "created_at": "2024-10-01T10:00:00.000000Z",
      "updated_at": "2024-10-04T16:30:00.000000Z"
    }
  ]
}
```

**CV Request Status Values:**
- `pending` - Request submitted
- `in_progress` - Being worked on
- `completed` - CV ready
- `rejected` - Request rejected

---

## 13. Get CV Request Status

**Endpoint:** `GET /api/v1/employee/cv/requests/{requestId}`
**Auth Required:** Yes (Employee)

### Response (200 OK)
```json
{
  "request": {
    "id": "uuid-here",
    "employee_id": "uuid-employee",
    "notes": "Modern tech CV needed",
    "status": "in_progress",
    "created_at": "2024-10-05T10:00:00.000000Z"
  }
}
```

---

# Employer APIs

## 1. Get Employer Profile

**Endpoint:** `GET /api/v1/employer/profile`
**Auth Required:** Yes (Employer)

### Response (200 OK)
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
    "created_at": "2024-01-10T08:00:00.000000Z",
    "industry": {
      "id": "uuid-here",
      "name": "Information Technology"
    }
  },
  "plan": {
    "id": "uuid-here",
    "name": "Professional Employer Plan",
    "description": "20 job posts per month",
    "type": "employer",
    "price": "49.99",
    "validity_days": 30,
    "features": [
      {
        "feature_name": "num_job_posts",
        "feature_value": "20"
      },
      {
        "feature_name": "featured_jobs",
        "feature_value": "5"
      }
    ]
  }
}
```

---

## 2. Update Employer Profile

**Endpoint:** `PUT /api/v1/employer/profile/update`
**Auth Required:** Yes (Employer)

### Request Payload
```json
{
  "company_name": "Tech Innovations Corp",
  "contact": "+1987654321",
  "address": {
    "street": "789 New Business Blvd",
    "city": "San Francisco",
    "state": "CA",
    "zip": "94105",
    "country": "USA"
  },
  "industry_type": "uuid-of-new-industry"
}
```

### Response (200 OK)
```json
{
  "message": "Profile updated."
}
```

---

## 3. Create Job

**Endpoint:** `POST /api/v1/employer/jobs`
**Auth Required:** Yes (Employer)

### Request Payload
```json
{
  "title": "Senior Full Stack Developer",
  "description": "We are seeking an experienced full stack developer...\n\nResponsibilities:\n- Develop web applications\n- Code review\n\nRequirements:\n- 5+ years experience",
  "salary": "$90,000 - $130,000",
  "location_id": "uuid-of-location",
  "category_id": "uuid-of-category"
}
```

### Response (201 Created)
```json
{
  "job_id": "uuid-of-new-job",
  "message": "Job created."
}
```

---

## 4. Get Job Details

**Endpoint:** `GET /api/v1/employer/jobs/{jobId}`
**Auth Required:** Yes (Employer)

### Response (200 OK)
```json
{
  "job": {
    "id": "uuid-here",
    "employer_id": "uuid-employer",
    "title": "Senior Full Stack Developer",
    "description": "Detailed job description...",
    "salary": "$90,000 - $130,000",
    "location_id": "uuid-location",
    "category_id": "uuid-category",
    "is_featured": false,
    "featured_end_date": null,
    "created_at": "2024-10-06T10:00:00.000000Z",
    "location": {
      "name": "San Francisco",
      "state": "CA"
    },
    "category": {
      "name": "Software Development"
    }
  }
}
```

---

## 5. Update Job

**Endpoint:** `PUT /api/v1/employer/jobs/{jobId}`
**Auth Required:** Yes (Employer)

### Request Payload
```json
{
  "title": "Senior Full Stack Developer (Updated)",
  "description": "Updated description...",
  "salary": "$95,000 - $135,000",
  "location_id": "uuid-of-location",
  "category_id": "uuid-of-category"
}
```

### Response (200 OK)
```json
{
  "message": "Job updated."
}
```

---

## 6. Delete Job

**Endpoint:** `DELETE /api/v1/employer/jobs/{jobId}`
**Auth Required:** Yes (Employer)

### Response (200 OK)
```json
{
  "message": "Job deleted."
}
```

---

## 7. Get Job Applications

**Endpoint:** `GET /api/v1/employer/jobs/{jobId}/applications`
**Auth Required:** Yes (Employer)

### Response (200 OK)
```json
{
  "applications": [
    {
      "id": "uuid-application",
      "employee": {
        "id": "uuid-employee",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "mobile": "+1234567890",
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

---

## 8. Update Application Status

**Endpoint:** `PUT /api/v1/employer/applications/{appId}/status`
**Auth Required:** Yes (Employer)

### Request Payload
```json
{
  "status": "shortlisted"
}
```

**Valid Status Values:**
- `applied`
- `shortlisted`
- `interview_scheduled`
- `selected`
- `rejected`

### Response (200 OK)
```json
{
  "message": "Status updated.",
  "whatsapp_sent": true
}
```

**Note:** Updating status triggers a WhatsApp notification to the employee.

---

# Admin APIs

## 1. Get Admin Profile

**Endpoint:** `GET /api/v1/admin/profile`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "admin": {
    "id": "uuid-here",
    "name": "Admin User",
    "email": "admin@jobportal.com",
    "role": "super_admin",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

## 2. Get Dashboard Statistics

**Endpoint:** `GET /api/v1/admin/dashboard/stats`
**Auth Required:** Yes (Admin)

### Response (200 OK)

**For Super Admin:**
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

**For Other Admin Roles:**
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

**Note:** Role-specific stats (total_commissions, total_coupons) are only visible to super_admin.

---

## 3. List All Admins

**Endpoint:** `GET /api/v1/admin/admins`
**Auth Required:** Yes (Super Admin Only)

### Response (200 OK)
```json
{
  "admins": [
    {
      "id": "uuid-here",
      "name": "John Admin",
      "email": "john@admin.com",
      "role": "employee_manager",
      "created_at": "2024-02-15T10:00:00.000000Z"
    }
  ]
}
```

---

## 4. Get Single Admin

**Endpoint:** `GET /api/v1/admin/admins/{id}`
**Auth Required:** Yes (Super Admin Only)

### Response (200 OK)
```json
{
  "admin": {
    "id": "uuid-here",
    "name": "John Admin",
    "email": "john@admin.com",
    "role": "employee_manager",
    "created_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

---

## 5. Create Admin

**Endpoint:** `POST /api/v1/admin/admins`
**Auth Required:** Yes (Super Admin Only)

### Request Payload
```json
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

### Response (201 Created)
```json
{
  "message": "Admin created successfully",
  "admin": {
    "id": "uuid-new-admin",
    "name": "Jane Manager",
    "email": "jane@admin.com",
    "role": "employer_manager"
  }
}
```

---

## 6. Update Admin

**Endpoint:** `PUT /api/v1/admin/admins/{id}`
**Auth Required:** Yes (Super Admin Only)

### Request Payload
```json
{
  "name": "Jane Updated",
  "email": "jane.updated@admin.com",
  "role": "employee_manager",
  "password": "NewPassword123!"
}
```

**Note:** Password is optional. Only include if changing password.

### Response (200 OK)
```json
{
  "message": "Admin updated successfully"
}
```

---

## 7. Delete Admin

**Endpoint:** `DELETE /api/v1/admin/admins/{id}`
**Auth Required:** Yes (Super Admin Only)

### Response (200 OK)
```json
{
  "message": "Admin deleted successfully"
}
```

### Error Response (403 Forbidden)
```json
{
  "message": "Cannot delete your own account"
}
```

---

## 8. List Employees

**Endpoint:** `GET /api/v1/admin/employees`
**Auth Required:** Yes (Admin - based on role)

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Pagination page |
| search | string | Search by name/email |

### Response (200 OK)
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
        "plan": {
          "name": "Basic Employee Plan"
        },
        "created_at": "2024-05-10T12:00:00.000000Z"
      }
    ],
    "total": 1250,
    "per_page": 20
  }
}
```

---

## 9. Get Single Employee

**Endpoint:** `GET /api/v1/admin/employees/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "employee": {
    "id": "uuid-here",
    "email": "john.doe@example.com",
    "name": "John Doe",
    "mobile": "+1234567890",
    "gender": "M",
    "dob": "1990-05-15",
    "address": {...},
    "education_details": [...],
    "experience_details": [...],
    "skills_details": [...],
    "cv_url": "/storage/cvs/cv_123.pdf",
    "plan": {...},
    "created_at": "2024-05-10T12:00:00.000000Z"
  }
}
```

---

## 10. Update Employee

**Endpoint:** `PUT /api/v1/admin/employees/{id}`
**Auth Required:** Yes (Admin - Employee Manager or Super Admin)

### Request Payload
```json
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "plan_id": "uuid-new-plan"
}
```

### Response (200 OK)
```json
{
  "message": "Employee updated successfully"
}
```

---

## 11. Delete Employee

**Endpoint:** `DELETE /api/v1/admin/employees/{id}`
**Auth Required:** Yes (Admin - Employee Manager or Super Admin)

### Response (200 OK)
```json
{
  "message": "Employee deleted successfully"
}
```

---

## 12. List Employers

**Endpoint:** `GET /api/v1/admin/employers`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "employers": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "company_name": "Tech Corp",
        "email": "hr@techcorp.com",
        "contact": "+1234567890",
        "industry": {
          "name": "Information Technology"
        },
        "plan": {
          "name": "Professional Plan"
        },
        "created_at": "2024-03-20T09:00:00.000000Z"
      }
    ],
    "total": 340
  }
}
```

---

## 13. Get Single Employer

**Endpoint:** `GET /api/v1/admin/employers/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "employer": {
    "id": "uuid-here",
    "company_name": "Tech Corp",
    "email": "hr@techcorp.com",
    "contact": "+1234567890",
    "address": {...},
    "industry_type": "uuid-industry",
    "plan": {...},
    "industry": {...},
    "created_at": "2024-03-20T09:00:00.000000Z"
  }
}
```

---

## 14. Update Employer

**Endpoint:** `PUT /api/v1/admin/employers/{id}`
**Auth Required:** Yes (Admin - Employer Manager or Super Admin)

### Request Payload
```json
{
  "company_name": "Tech Corp Updated",
  "email": "newhr@techcorp.com",
  "plan_id": "uuid-new-plan"
}
```

### Response (200 OK)
```json
{
  "message": "Employer updated successfully"
}
```

---

## 15. Delete Employer

**Endpoint:** `DELETE /api/v1/admin/employers/{id}`
**Auth Required:** Yes (Admin - Employer Manager or Super Admin)

### Response (200 OK)
```json
{
  "message": "Employer deleted successfully"
}
```

---

## 16. List All Jobs

**Endpoint:** `GET /api/v1/admin/jobs`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "jobs": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-here",
        "title": "Senior Developer",
        "employer": {
          "company_name": "Tech Corp"
        },
        "location": {
          "name": "New York"
        },
        "category": {
          "name": "Software Development"
        },
        "is_featured": true,
        "created_at": "2024-10-01T10:00:00.000000Z"
      }
    ],
    "total": 890
  }
}
```

---

## 17. Create Coupon

**Endpoint:** `POST /api/v1/admin/coupons`
**Auth Required:** Yes (Super Admin)

### Request Payload
```json
{
  "code": "SAVE20",
  "discount_percentage": 20.00,
  "expiry_date": "2025-12-31",
  "staff_id": "uuid-of-staff-member"
}
```

### Response (201 Created)
```json
{
  "message": "Coupon created successfully",
  "coupon": {
    "id": "uuid-here",
    "code": "SAVE20",
    "discount_percentage": "20.00",
    "expiry_date": "2025-12-31",
    "staff_id": "uuid-staff"
  }
}
```

---

## 18. List Coupons

**Endpoint:** `GET /api/v1/admin/coupons`
**Auth Required:** Yes (Super Admin)

### Response (200 OK)
```json
{
  "coupons": [
    {
      "id": "uuid-here",
      "code": "SAVE20",
      "discount_percentage": "20.00",
      "expiry_date": "2025-12-31",
      "staff": {
        "name": "Sales Staff Member",
        "email": "staff@company.com"
      },
      "created_at": "2024-09-01T10:00:00.000000Z"
    }
  ]
}
```

---

## 19. Add Manual Commission

**Endpoint:** `POST /api/v1/admin/commissions/manual`
**Auth Required:** Yes (Super Admin or Plan Upgrade Manager)

### Request Payload
```json
{
  "staff_id": "uuid-of-staff",
  "amount_earned": 150.00,
  "payment_id": "uuid-of-payment",
  "notes": "Manual commission for offline sale"
}
```

### Response (201 Created)
```json
{
  "message": "Commission added successfully",
  "commission": {
    "id": "uuid-here",
    "staff_id": "uuid-staff",
    "amount_earned": "150.00",
    "type": "manual",
    "created_at": "2024-10-06T15:00:00.000000Z"
  }
}
```

---

## 20. Get All Commissions

**Endpoint:** `GET /api/v1/admin/commissions/all`
**Auth Required:** Yes (Super Admin)

### Response (200 OK)
```json
{
  "commissions": [
    {
      "id": "uuid-here",
      "staff": {
        "name": "John Staff",
        "email": "john@staff.com"
      },
      "amount_earned": "150.00",
      "type": "coupon_based",
      "payment": {
        "id": "uuid-payment",
        "amount": "49.99"
      },
      "created_at": "2024-10-05T12:00:00.000000Z"
    }
  ],
  "total_commissions": "15750.50"
}
```

---

## 21. Get Staff Commissions

**Endpoint:** `GET /api/v1/admin/commissions/my`
**Auth Required:** Yes (Admin - Staff Member)

### Response (200 OK)
```json
{
  "commissions": [
    {
      "id": "uuid-here",
      "amount_earned": "150.00",
      "type": "coupon_based",
      "created_at": "2024-10-05T12:00:00.000000Z"
    }
  ],
  "total_earned": "850.00"
}
```

---

## 22. Get CV Requests

**Endpoint:** `GET /api/v1/admin/cv-requests`
**Auth Required:** Yes (Admin - Employee Manager or Super Admin)

### Response (200 OK)
```json
{
  "cv_requests": [
    {
      "id": "uuid-here",
      "employee": {
        "name": "John Doe",
        "email": "john@example.com"
      },
      "notes": "Need modern tech CV",
      "preferred_template": "Modern Tech",
      "status": "pending",
      "created_at": "2024-10-05T10:00:00.000000Z"
    }
  ]
}
```

---

## 23. Update CV Request Status

**Endpoint:** `PUT /api/v1/admin/cv-requests/{id}/status`
**Auth Required:** Yes (Admin - Employee Manager or Super Admin)

### Request Payload
```json
{
  "status": "completed",
  "completed_cv_url": "/storage/professional_cvs/cv_123_final.pdf",
  "admin_notes": "CV completed with modern template"
}
```

**Valid Status Values:**
- `pending`
- `in_progress`
- `completed`
- `rejected`

### Response (200 OK)
```json
{
  "message": "CV request status updated"
}
```

---

# Plan APIs

## 1. Get All Plans

**Endpoint:** `GET /api/v1/plans`
**Auth Required:** No (Public)

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | Filter by type: employee/employer |

### Response (200 OK)
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
          "id": "uuid-feature",
          "feature_name": "num_job_applies",
          "feature_value": "5"
        },
        {
          "id": "uuid-feature-2",
          "feature_name": "cv_downloads",
          "feature_value": "3"
        }
      ]
    },
    {
      "id": "uuid-here-2",
      "name": "Premium Employee Plan",
      "description": "Unlimited access for serious job seekers",
      "type": "employee",
      "price": "29.99",
      "validity_days": 30,
      "features": [
        {
          "feature_name": "num_job_applies",
          "feature_value": "unlimited"
        },
        {
          "feature_name": "professional_cv",
          "feature_value": "1"
        }
      ]
    }
  ]
}
```

---

## 2. Get Single Plan

**Endpoint:** `GET /api/v1/plans/{id}`
**Auth Required:** No (Public)

### Response (200 OK)
```json
{
  "plan": {
    "id": "uuid-here",
    "name": "Basic Employee Plan",
    "description": "Perfect for job seekers starting out",
    "type": "employee",
    "price": "9.99",
    "validity_days": 30,
    "features": [...]
  }
}
```

---

## 3. Create Plan

**Endpoint:** `POST /api/v1/plans`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "Enterprise Employer Plan",
  "description": "Unlimited job posts for large companies",
  "type": "employer",
  "price": 199.99,
  "validity_days": 30
}
```

### Response (201 Created)
```json
{
  "message": "Plan created successfully",
  "plan": {
    "id": "uuid-new-plan",
    "name": "Enterprise Employer Plan",
    "type": "employer",
    "price": "199.99"
  }
}
```

---

## 4. Update Plan

**Endpoint:** `PUT /api/v1/plans/{id}`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "Enterprise Employer Plan - Updated",
  "description": "New description",
  "price": 179.99,
  "validity_days": 30
}
```

### Response (200 OK)
```json
{
  "message": "Plan updated successfully"
}
```

---

## 5. Delete Plan

**Endpoint:** `DELETE /api/v1/plans/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "message": "Plan deleted successfully"
}
```

---

## 6. Add Plan Feature

**Endpoint:** `POST /api/v1/plans/{planId}/features`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "feature_name": "featured_jobs",
  "feature_value": "10"
}
```

### Response (201 Created)
```json
{
  "message": "Plan feature added successfully",
  "feature": {
    "id": "uuid-feature",
    "plan_id": "uuid-plan",
    "feature_name": "featured_jobs",
    "feature_value": "10"
  }
}
```

---

## 7. Remove Plan Feature

**Endpoint:** `DELETE /api/v1/plans/features/{featureId}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "message": "Plan feature removed successfully"
}
```

---

# Catalog APIs

## 1. Get Industries

**Endpoint:** `GET /api/v1/catalogs/industries`
**Auth Required:** No (Public)

### Response (200 OK)
```json
{
  "industries": [
    {
      "id": "uuid-here",
      "name": "Information Technology",
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    {
      "id": "uuid-here-2",
      "name": "Healthcare",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

## 2. Create Industry

**Endpoint:** `POST /api/v1/catalogs/industries`
**Auth Required:** Yes (Admin - Catalog Manager or Super Admin)

### Request Payload
```json
{
  "name": "Financial Services"
}
```

### Response (201 Created)
```json
{
  "message": "Industry created successfully",
  "industry": {
    "id": "uuid-new",
    "name": "Financial Services"
  }
}
```

---

## 3. Update Industry

**Endpoint:** `PUT /api/v1/catalogs/industries/{id}`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "Financial Services & Banking"
}
```

### Response (200 OK)
```json
{
  "message": "Industry updated successfully"
}
```

---

## 4. Delete Industry

**Endpoint:** `DELETE /api/v1/catalogs/industries/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "message": "Industry deleted successfully"
}
```

---

## 5. Get Locations

**Endpoint:** `GET /api/v1/catalogs/locations`
**Auth Required:** No (Public)

### Response (200 OK)
```json
{
  "locations": [
    {
      "id": "uuid-here",
      "name": "New York",
      "state": "NY",
      "country": "USA",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

## 6. Create Location

**Endpoint:** `POST /api/v1/catalogs/locations`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "San Francisco",
  "state": "CA",
  "country": "USA"
}
```

### Response (201 Created)
```json
{
  "message": "Location created successfully",
  "location": {
    "id": "uuid-new",
    "name": "San Francisco",
    "state": "CA",
    "country": "USA"
  }
}
```

---

## 7. Update Location

**Endpoint:** `PUT /api/v1/catalogs/locations/{id}`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "San Francisco Bay Area",
  "state": "CA",
  "country": "USA"
}
```

### Response (200 OK)
```json
{
  "message": "Location updated successfully"
}
```

---

## 8. Delete Location

**Endpoint:** `DELETE /api/v1/catalogs/locations/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "message": "Location deleted successfully"
}
```

---

## 9. Get Job Categories

**Endpoint:** `GET /api/v1/catalogs/categories`
**Auth Required:** No (Public)

### Response (200 OK)
```json
{
  "categories": [
    {
      "id": "uuid-here",
      "name": "Software Development",
      "created_at": "2024-01-01T00:00:00.000000Z"
    },
    {
      "id": "uuid-here-2",
      "name": "Marketing & Sales",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

## 10. Create Job Category

**Endpoint:** `POST /api/v1/catalogs/categories`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "Data Science & Analytics"
}
```

### Response (201 Created)
```json
{
  "message": "Job category created successfully",
  "category": {
    "id": "uuid-new",
    "name": "Data Science & Analytics"
  }
}
```

---

## 11. Update Job Category

**Endpoint:** `PUT /api/v1/catalogs/categories/{id}`
**Auth Required:** Yes (Admin)

### Request Payload
```json
{
  "name": "Data Science, AI & Analytics"
}
```

### Response (200 OK)
```json
{
  "message": "Job category updated successfully"
}
```

---

## 12. Delete Job Category

**Endpoint:** `DELETE /api/v1/catalogs/categories/{id}`
**Auth Required:** Yes (Admin)

### Response (200 OK)
```json
{
  "message": "Job category deleted successfully"
}
```

---

# Payment APIs

## 1. Subscribe to Plan

**Endpoint:** `POST /api/v1/payments/subscribe`
**Auth Required:** Yes (Employee or Employer)

### Request Payload
```json
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

### Response (201 Created)
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
    "transaction_id": "txn_123456789",
    "created_at": "2024-10-06T15:30:00.000000Z"
  },
  "subscription_expires_at": "2024-11-06T15:30:00.000000Z"
}
```

---

## 2. Verify Payment

**Endpoint:** `POST /api/v1/payments/verify`
**Auth Required:** Yes

### Request Payload
```json
{
  "payment_id": "uuid-of-payment",
  "transaction_id": "txn_123456789"
}
```

**Note:** The field name is `transaction_id`, not `transaction_reference`.

### Response (200 OK)
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

---

## 3. Get Payment History

**Endpoint:** `GET /api/v1/payments/history`
**Auth Required:** Yes

### Response (200 OK)
```json
{
  "payments": [
    {
      "id": "uuid-payment",
      "plan": {
        "name": "Basic Employee Plan"
      },
      "amount": "9.99",
      "discount_amount": "2.00",
      "final_amount": "7.99",
      "coupon_code": "SAVE20",
      "payment_status": "completed",
      "transaction_id": "txn_123456789",
      "created_at": "2024-10-06T15:30:00.000000Z"
    }
  ]
}
```

---

## 4. Validate Coupon

**Endpoint:** `POST /api/v1/coupons/validate`
**Auth Required:** No (Public)

### Request Payload
```json
{
  "coupon_code": "SAVE20",
  "plan_id": "uuid-of-plan"
}
```

**Note:** The field name is `coupon_code`, not `code`.

### Response (200 OK)
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

### Error Response (200 OK - Invalid Coupon)
```json
{
  "valid": false,
  "message": "Invalid or expired coupon code"
}
```

**Note:** The API returns 200 status code even for invalid coupons, with `valid: false` in the response body.

### Validation Error Response (422 Unprocessable Entity)
```json
{
  "errors": {
    "coupon_code": ["The coupon code field is required."],
    "plan_id": ["The plan id field is required."]
  }
}
```

---

# Public APIs

## 1. Public Job Search

**Endpoint:** `GET /api/v1/jobs/search`
**Auth Required:** No

### Query Parameters
Same as Employee Job Search

### Response
Same format as Employee Job Search

---

## 2. Get Public Content List

**Endpoint:** `GET /api/v1/content`
**Auth Required:** No

### Response (200 OK)
```json
{
  "content": [
    {
      "id": "uuid-here",
      "title": "About Us",
      "slug": "about-us",
      "excerpt": "Learn more about our company...",
      "created_at": "2024-01-15T10:00:00.000000Z"
    },
    {
      "id": "uuid-here-2",
      "title": "Privacy Policy",
      "slug": "privacy-policy",
      "excerpt": "Our commitment to your privacy...",
      "created_at": "2024-01-15T10:00:00.000000Z"
    }
  ]
}
```

---

## 3. Get Public Content Detail

**Endpoint:** `GET /api/v1/content/{identifier}`
**Auth Required:** No

### URL Parameters
`identifier` can be either:
- UUID (e.g., `uuid-here`)
- Slug (e.g., `about-us`)

### Response (200 OK)
```json
{
  "content": {
    "id": "uuid-here",
    "title": "About Us",
    "slug": "about-us",
    "body": "<h1>Welcome to Job Portal</h1><p>Full content here...</p>",
    "meta_description": "Learn more about Job Portal",
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z"
  }
}
```

---

## 4. Get Public Media

**Endpoint:** `GET /api/v1/media/{id}`
**Auth Required:** No

### Response (200 OK)
```json
{
  "media": {
    "id": "uuid-here",
    "title": "Company Logo",
    "file_url": "/storage/media/logo.png",
    "file_type": "image/png",
    "file_size": 15360,
    "created_at": "2024-01-15T10:00:00.000000Z"
  }
}
```

---

# Error Handling

## Standard Error Response Format

All errors follow a consistent JSON structure:

```json
{
  "message": "Error description",
  "errors": {
    "field_name": [
      "Specific validation error"
    ]
  }
}
```

---

## HTTP Status Codes

| Status Code | Meaning | When Used |
|-------------|---------|-----------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (resource created) |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Authentication required or failed |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server-side error |

---

## Common Error Examples

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

**Solution:** Include valid Bearer token in Authorization header

---

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

**Solution:** User doesn't have permission for this action

---

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

**Solution:** Check if the resource ID exists

---

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

**Solution:** Fix validation errors and retry

---

## Rate Limiting

**Status Code:** 429 Too Many Requests

```json
{
  "message": "Too Many Requests"
}
```

**Headers:**
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `Retry-After`: Seconds to wait before retry

---

## Best Practices for Frontend Integration

### 1. Token Management
```javascript
// Store token after login
localStorage.setItem('auth_token', response.token);

// Include in all requests
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};
```

### 2. Error Handling
```javascript
try {
  const response = await fetch(url, {
    method: 'POST',
    headers: headers,
    body: JSON.stringify(data)
  });

  if (!response.ok) {
    const error = await response.json();
    // Handle error based on status code
    if (response.status === 401) {
      // Redirect to login
    } else if (response.status === 422) {
      // Show validation errors
      console.log(error.errors);
    }
  }

  return await response.json();
} catch (error) {
  console.error('Network error:', error);
}
```

### 3. File Upload
```javascript
const formData = new FormData();
formData.append('cv_file', fileInput.files[0]);

const response = await fetch('/api/v1/employee/cv/upload', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
    // Don't set Content-Type for FormData
  },
  body: formData
});
```

### 4. Pagination Handling
```javascript
const loadJobs = async (page = 1) => {
  const response = await fetch(
    `/api/v1/employee/jobs/search?page=${page}&q=developer`,
    { headers }
  );
  const data = await response.json();

  // data.jobs.current_page
  // data.jobs.last_page
  // data.jobs.data (actual items)
};
```

---

## WebSocket/Real-time Updates

**Note:** WhatsApp notifications are handled server-side via background jobs. Frontend should poll for updates or implement a notification check mechanism.

### Recommended Polling for Application Status
```javascript
// Poll every 30 seconds for new status updates
setInterval(async () => {
  const response = await fetch('/api/v1/employee/jobs/applied', {
    headers
  });
  const data = await response.json();
  // Check for status changes
}, 30000);
```

---

## Testing Endpoints

### Using cURL
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"user@example.com","password":"password123"}'

# Get Profile (with token)
curl -X GET http://localhost:8000/api/v1/employee/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Using Postman
1. Create environment variable `base_url` = `http://localhost:8000/api/v1`
2. Create environment variable `token` (set after login)
3. Set Authorization header: `Bearer {{token}}`
4. Test endpoints using `{{base_url}}/endpoint`

---

## Additional Notes

### Date Format
All dates use ISO 8601 format: `YYYY-MM-DDTHH:MM:SS.000000Z`

### UUID Format
All IDs are UUIDs in format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

### JSON Fields
Fields marked as `JSONB` in database accept nested JSON objects/arrays.

### Pagination
- Default: 20 items per page
- Access via `?page=N` query parameter
- Response includes: `current_page`, `last_page`, `total`, `data`

---

**End of Documentation**

For questions or issues, please contact the backend development team.

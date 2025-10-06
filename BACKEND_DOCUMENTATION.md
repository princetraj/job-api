# Job Portal API - Backend Development Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [System Architecture](#system-architecture)
3. [Development Environment Setup](#development-environment-setup)
4. [Project Structure](#project-structure)
5. [Database Architecture](#database-architecture)
6. [Authentication & Authorization](#authentication--authorization)
7. [Business Logic & Workflows](#business-logic--workflows)
8. [Controllers & Services](#controllers--services)
9. [Models & Relationships](#models--relationships)
10. [Middleware & Security](#middleware--security)
11. [File Storage & Media Management](#file-storage--media-management)
12. [Third-Party Integrations](#third-party-integrations)
13. [Testing Strategy](#testing-strategy)
14. [Deployment Guide](#deployment-guide)
15. [Performance Optimization](#performance-optimization)
16. [Troubleshooting & Debugging](#troubleshooting--debugging)

---

# Introduction

## Overview

The Job Portal API is a **RESTful Laravel 8 backend** that powers a comprehensive job matching platform connecting employees (job seekers) with employers. The system features subscription-based access, role-based admin controls, commission tracking, and real-time notifications.

## Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Framework** | Laravel | 8.75+ |
| **Language** | PHP | 7.3+ / 8.0+ |
| **Database** | MySQL | 5.7+ / 8.0+ |
| **Authentication** | Laravel Sanctum | 2.11+ |
| **Queue System** | Laravel Queue | Built-in |
| **Cache** | File/Redis | - |
| **Storage** | Local/S3 | - |

## Key Features

- **Multi-tenancy**: Employees, Employers, Admins
- **Role-Based Access Control (RBAC)**: 5 admin roles
- **Subscription Plans**: Tiered pricing for both user types
- **Commission System**: Coupon-based and manual tracking
- **CV Management**: Upload, generate, and professional CV requests
- **WhatsApp Integration**: Asynchronous notifications
- **Payment Gateway**: Subscription and payment tracking

---

# System Architecture

## Architectural Pattern

The application follows **Laravel's MVC pattern** with additional service layer for complex business logic:

```
┌─────────────────────────────────────────────────────────┐
│                     Frontend Layer                       │
│              (Next.js + React Admin Panel)               │
└─────────────────────────────────────────────────────────┘
                           ↓ HTTP/REST
┌─────────────────────────────────────────────────────────┐
│                    API Layer (Routes)                    │
│                  /api/v1/* endpoints                     │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│                  Middleware Layer                        │
│        (Auth, CORS, Throttling, Validation)             │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│                  Controller Layer                        │
│     (Request Handling, Response Formatting)              │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│               Business Logic Layer                       │
│          (Services, Events, Observers)                   │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│                    Model Layer                           │
│        (Eloquent ORM, Relationships, Scopes)             │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│                  Database Layer (MySQL)                  │
└─────────────────────────────────────────────────────────┘

                    ↓ Async Jobs ↓
┌─────────────────────────────────────────────────────────┐
│              Third-Party Services                        │
│        (WhatsApp API, Payment Gateway, CV Service)       │
└─────────────────────────────────────────────────────────┘
```

## Design Principles

1. **Separation of Concerns**: Controllers handle HTTP, Services handle business logic
2. **DRY (Don't Repeat Yourself)**: Shared logic in traits and base classes
3. **Single Responsibility**: Each class has one clear purpose
4. **Dependency Injection**: Laravel's service container
5. **RESTful API Design**: Resource-based endpoints with standard HTTP methods

---

# Development Environment Setup

## Prerequisites

```bash
# Required
- PHP 7.3+ or 8.0+
- Composer 2.0+
- MySQL 5.7+ or 8.0+
- Node.js 14+ (for asset compilation)
- Git

# Recommended
- WAMP/XAMPP (Windows) or MAMP (Mac)
- VS Code with PHP extensions
- Postman or Insomnia for API testing
```

## Installation Steps

### 1. Clone Repository
```bash
git clone <repository-url>
cd job-portal-api
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE job_portal;
exit;

# Configure .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=job_portal
DB_USERNAME=root
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed
```

### 5. Storage Setup
```bash
# Create symbolic link for storage
php artisan storage:link

# Set permissions (Unix/Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 6. Start Development Server
```bash
php artisan serve
# Access at: http://localhost:8000
```

---

# Project Structure

## Directory Overview

```
job-portal-api/
├── app/
│   ├── Console/
│   │   └── Kernel.php              # Schedule commands
│   ├── Exceptions/
│   │   └── Handler.php             # Global exception handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/                # API Controllers
│   │   │       ├── AdminController.php
│   │   │       ├── AuthController.php
│   │   │       ├── CatalogController.php
│   │   │       ├── CommissionController.php
│   │   │       ├── ContentController.php
│   │   │       ├── EmployeeController.php
│   │   │       ├── EmployerController.php
│   │   │       ├── JobController.php
│   │   │       ├── MediaController.php
│   │   │       ├── PaymentController.php
│   │   │       └── PlanController.php
│   │   ├── Middleware/             # HTTP middleware
│   │   │   ├── Authenticate.php
│   │   │   └── ...
│   │   └── Kernel.php              # HTTP kernel
│   ├── Models/                     # Eloquent models
│   │   ├── Admin.php
│   │   ├── CommissionTransaction.php
│   │   ├── Content.php
│   │   ├── Coupon.php
│   │   ├── CVRequest.php
│   │   ├── Employee.php
│   │   ├── Employer.php
│   │   ├── Industry.php
│   │   ├── Job.php
│   │   ├── JobApplication.php
│   │   ├── JobCategory.php
│   │   ├── Location.php
│   │   ├── Media.php
│   │   ├── Payment.php
│   │   ├── Plan.php
│   │   ├── PlanFeature.php
│   │   ├── ShortlistedJob.php
│   │   └── User.php
│   └── Providers/                  # Service providers
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       └── RouteServiceProvider.php
├── bootstrap/
│   └── cache/                      # Bootstrap cache
├── config/                         # Configuration files
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── sanctum.php
│   └── ...
├── database/
│   ├── factories/                  # Model factories
│   ├── migrations/                 # Database migrations
│   │   ├── 2025_10_06_130406_create_employees_table.php
│   │   ├── 2025_10_06_130446_create_employers_table.php
│   │   └── ...                     # 23 migration files
│   └── seeders/                    # Database seeders
│       └── DatabaseSeeder.php
├── public/
│   ├── index.php                   # Application entry point
│   └── storage/                    # Symlink to storage/app/public
├── resources/
│   └── views/                      # Blade templates (if needed)
├── routes/
│   ├── api.php                     # API routes
│   ├── web.php                     # Web routes
│   └── channels.php                # Broadcast channels
├── storage/
│   ├── app/
│   │   ├── public/                 # Publicly accessible files
│   │   │   ├── cvs/               # CV uploads
│   │   │   ├── media/             # Media files
│   │   │   └── professional_cvs/  # Professional CVs
│   │   └── private/                # Private files
│   ├── framework/                  # Framework cache
│   └── logs/                       # Application logs
├── tests/
│   ├── Feature/                    # Feature tests
│   └── Unit/                       # Unit tests
├── vendor/                         # Composer dependencies
├── .env                            # Environment configuration
├── .env.example                    # Environment template
├── artisan                         # Artisan CLI
├── composer.json                   # Composer dependencies
├── composer.lock                   # Locked dependencies
└── README.md                       # Project readme
```

## Key Files Explanation

### Routes (`routes/api.php`)
- Defines all API endpoints
- Groups routes by user type
- Applies middleware (auth, throttle)
- Version prefix: `/api/v1/`

### Controllers (`app/Http/Controllers/Api/`)
- Handle HTTP requests
- Validate input
- Return JSON responses
- Keep logic minimal (delegate to services)

### Models (`app/Models/`)
- Define database structure
- Eloquent relationships
- Accessors/Mutators
- Query scopes

### Migrations (`database/migrations/`)
- Version control for database
- Schema definitions
- Foreign key constraints
- Indexes

---

# Database Architecture

## Entity Relationship Diagram (ERD)

```
┌─────────────┐         ┌──────────────┐
│  Employees  │         │  Employers   │
├─────────────┤         ├──────────────┤
│ id (PK)     │         │ id (PK)      │
│ email       │         │ company_name │
│ mobile      │         │ email        │
│ name        │         │ contact      │
│ plan_id(FK) │         │ plan_id (FK) │
└─────┬───────┘         └──────┬───────┘
      │                        │
      │                        │
      ├────────┐      ┌────────┤
      │        │      │        │
      ▼        ▼      ▼        ▼
┌─────────────────────────────────┐
│      JobApplications            │
├─────────────────────────────────┤
│ id (PK)                         │
│ job_id (FK)                     │
│ employee_id (FK)                │
│ application_status (ENUM)       │
│ applied_at                      │
└─────────────────────────────────┘
      ▲
      │
      │
┌─────┴───────┐
│    Jobs     │
├─────────────┤
│ id (PK)     │
│ employer_id │
│ title       │
│ description │
│ location_id │
│ category_id │
│ is_featured │
└─────────────┘

┌──────────────┐       ┌──────────────┐
│    Plans     │       │PlanFeatures  │
├──────────────┤       ├──────────────┤
│ id (PK)      │◄──────┤ plan_id (FK) │
│ name         │       │ feature_name │
│ type (ENUM)  │       │ feature_value│
│ price        │       └──────────────┘
│ validity_days│
└──────────────┘

┌──────────────┐       ┌──────────────────────────┐
│   Coupons    │       │ CommissionTransactions   │
├──────────────┤       ├──────────────────────────┤
│ id (PK)      │       │ id (PK)                  │
│ code         │       │ staff_id (FK)            │
│ discount_%   │       │ payment_id (FK)          │
│ expiry_date  │       │ amount_earned            │
│ staff_id(FK) │       │ type (ENUM)              │
└──────────────┘       └──────────────────────────┘

┌──────────────┐       ┌──────────────┐
│   Admins     │       │  CVRequests  │
├──────────────┤       ├──────────────┤
│ id (PK)      │       │ id (PK)      │
│ name         │       │ employee_id  │
│ email        │       │ status       │
│ role (ENUM)  │       │ notes        │
│ password     │       │completed_url │
└──────────────┘       └──────────────┘
```

## Database Tables Summary

### User Tables
| Table | Records | Purpose |
|-------|---------|---------|
| employees | ~1000+ | Job seekers |
| employers | ~300+ | Companies posting jobs |
| admins | ~5-10 | Platform administrators |

### Core Business Tables
| Table | Records | Purpose |
|-------|---------|---------|
| jobs | ~500+ | Job postings |
| job_applications | ~5000+ | Application records |
| shortlisted_jobs | ~1000+ | Saved jobs by employees |

### Catalog Tables
| Table | Records | Purpose |
|-------|---------|---------|
| industries | ~50 | Industry categories |
| locations | ~100+ | Cities/states |
| job_categories | ~30 | Job type categories |

### Subscription & Payment Tables
| Table | Records | Purpose |
|-------|---------|---------|
| plans | ~10 | Subscription plans |
| plan_features | ~30 | Plan feature definitions |
| payments | ~2000+ | Payment transactions |
| coupons | ~50 | Discount coupons |
| commission_transactions | ~500+ | Sales commissions |

### Content Management Tables
| Table | Records | Purpose |
|-------|---------|---------|
| contents | ~20 | CMS pages |
| media | ~100+ | Media library |
| cv_requests | ~100+ | Professional CV requests |

## Key Database Constraints

### Foreign Keys
```sql
-- Examples
ALTER TABLE employees ADD CONSTRAINT fk_employees_plan
  FOREIGN KEY (plan_id) REFERENCES plans(id);

ALTER TABLE jobs ADD CONSTRAINT fk_jobs_employer
  FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE CASCADE;

ALTER TABLE job_applications ADD CONSTRAINT fk_applications_job
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE;
```

### Indexes
```sql
-- Performance optimization
CREATE INDEX idx_jobs_employer ON jobs(employer_id);
CREATE INDEX idx_jobs_location ON jobs(location_id);
CREATE INDEX idx_jobs_category ON jobs(category_id);
CREATE INDEX idx_applications_employee ON job_applications(employee_id);
CREATE INDEX idx_applications_status ON job_applications(application_status);
```

### Unique Constraints
```sql
-- Data integrity
ALTER TABLE employees ADD UNIQUE (email);
ALTER TABLE employees ADD UNIQUE (mobile);
ALTER TABLE employers ADD UNIQUE (email);
ALTER TABLE coupons ADD UNIQUE (code);
```

---

# Authentication & Authorization

## Laravel Sanctum Implementation

### Configuration (`config/sanctum.php`)
```php
<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS',
        sprintf('%s%s', 'localhost,localhost:3000,127.0.0.1',
        Sanctum::currentApplicationUrlWithPort())
    )),
    'guard' => ['web'],
    'expiration' => null, // Tokens never expire
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

### Multi-Guard Authentication

The system uses **multiple authenticatable models**:

#### Employee Model (`app/Models/Employee.php`)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'email', 'mobile', 'password', 'name', 'gender',
        'dob', 'address', 'education_details',
        'experience_details', 'skills_details', 'cv_url', 'plan_id'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'address' => 'array',
        'education_details' => 'array',
        'experience_details' => 'array',
        'skills_details' => 'array',
        'dob' => 'date',
    ];

    // Automatically hash password
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // Relationships
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
```

#### Employer Model (`app/Models/Employer.php`)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Employer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'company_name', 'email', 'contact',
        'address', 'industry_type', 'password', 'plan_id'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'address' => 'array',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}
```

#### Admin Model (`app/Models/Admin.php`)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password'];

    // Role-based authorization
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }
}
```

### Login Implementation (`AuthController.php`)

```php
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'identifier' => 'required|string', // email or mobile
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $identifier = $request->identifier;
    $password = $request->password;

    // Try Employee
    $employee = Employee::where('email', $identifier)
        ->orWhere('mobile', $identifier)
        ->first();

    if ($employee && Hash::check($password, $employee->password)) {
        $token = $employee->createToken('employee-token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user_type' => 'employee',
            'user' => $employee,
        ], 200);
    }

    // Try Employer
    $employer = Employer::where('email', $identifier)->first();

    if ($employer && Hash::check($password, $employer->password)) {
        $token = $employer->createToken('employer-token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user_type' => 'employer',
            'user' => $employer,
        ], 200);
    }

    // Try Admin
    $admin = Admin::where('email', $identifier)->first();

    if ($admin && Hash::check($password, $admin->password)) {
        $token = $admin->createToken('admin-token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user_type' => 'admin',
            'user' => $admin,
        ], 200);
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
}
```

## Role-Based Access Control (RBAC)

### Admin Roles & Permissions

```php
// config/permissions.php
<?php

return [
    'roles' => [
        'super_admin' => [
            'employees' => ['create', 'read', 'update', 'delete'],
            'employers' => ['create', 'read', 'update', 'delete'],
            'jobs' => ['create', 'read', 'update', 'delete'],
            'plans' => ['create', 'read', 'update', 'delete'],
            'catalogs' => ['create', 'read', 'update', 'delete'],
            'commissions' => ['view_all', 'manual_add'],
            'content' => ['create', 'read', 'update', 'delete'],
            'media' => ['create', 'read', 'update', 'delete'],
            'admins' => ['create', 'read', 'update', 'delete'],
        ],

        'employee_manager' => [
            'employees' => ['create', 'read', 'update', 'delete'],
            'cv_requests' => ['read', 'update'],
            'employers' => ['read'],
            'jobs' => [],
        ],

        'employer_manager' => [
            'employers' => ['create', 'read', 'update', 'delete'],
            'jobs' => ['create', 'read', 'update', 'delete'],
            'employees' => ['read'],
        ],

        'plan_upgrade_manager' => [
            'plans' => ['create', 'read', 'update', 'delete'],
            'commissions' => ['view_all', 'manual_add'],
            'employees' => ['read'],
            'employers' => ['read'],
        ],

        'catalog_manager' => [
            'catalogs' => ['create', 'read', 'update', 'delete'],
            'jobs' => ['read'],
        ],
    ],
];
```

### Authorization Middleware

```php
// app/Http/Middleware/CheckAdminRole.php
<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdminRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $admin = $request->user();

        if (!$admin || !($admin instanceof \App\Models\Admin)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($admin->role, $roles)) {
            return response()->json([
                'message' => 'Insufficient permissions'
            ], 403);
        }

        return $next($request);
    }
}
```

### Usage in Controllers

```php
// AdminController.php
public function createAdmin(Request $request)
{
    $admin = $request->user();

    // Only super admin can create admins
    if (!$admin->isSuperAdmin()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // ... create admin logic
}
```

---

# Business Logic & Workflows

## Critical Workflows

### 1. Job Application Workflow

```php
// EmployeeController@applyForJob
public function applyForJob(Request $request, $jobId)
{
    $employee = $request->user();
    $job = Job::find($jobId);

    if (!$job) {
        return response()->json(['message' => 'Job not found'], 404);
    }

    // Check if already applied
    $existingApplication = JobApplication::where('job_id', $jobId)
        ->where('employee_id', $employee->id)
        ->first();

    if ($existingApplication) {
        return response()->json(['message' => 'Already applied'], 400);
    }

    // Create application
    $application = JobApplication::create([
        'job_id' => $jobId,
        'employee_id' => $employee->id,
        'application_status' => 'applied',
        'applied_at' => now(),
    ]);

    // Trigger WhatsApp notification to employer (async)
    $this->notifyEmployerNewApplication($job->employer, $employee, $job);

    return response()->json(['message' => 'Application submitted.'], 201);
}

private function notifyEmployerNewApplication($employer, $employee, $job)
{
    // Queue WhatsApp notification
    dispatch(new SendWhatsAppNotification([
        'to' => $employer->contact,
        'message' => "New application from {$employee->name} for {$job->title}",
    ]));
}
```

### 2. Application Status Update Workflow

```php
// EmployerController@updateApplicationStatus
public function updateApplicationStatus(Request $request, $appId)
{
    $validator = Validator::make($request->all(), [
        'status' => 'required|string|in:applied,shortlisted,interview_scheduled,selected,rejected',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $application = JobApplication::with('job', 'employee')->find($appId);

    if (!$application) {
        return response()->json(['message' => 'Application not found'], 404);
    }

    // Verify ownership
    if ($application->job->employer_id != $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Update status
    $application->update([
        'application_status' => $request->status,
    ]);

    // Notify employee via WhatsApp (async)
    $this->notifyEmployeeStatusChange(
        $application->employee,
        $application->job,
        $request->status
    );

    return response()->json([
        'message' => 'Status updated.',
        'whatsapp_sent' => true,
    ], 200);
}

private function notifyEmployeeStatusChange($employee, $job, $status)
{
    $messages = [
        'shortlisted' => "Congratulations! You've been shortlisted for {$job->title}",
        'interview_scheduled' => "Interview scheduled for {$job->title}",
        'selected' => "Congratulations! You've been selected for {$job->title}",
        'rejected' => "Thank you for applying to {$job->title}",
    ];

    dispatch(new SendWhatsAppNotification([
        'to' => $employee->mobile,
        'message' => $messages[$status] ?? "Application status updated",
    ]));
}
```

### 3. Commission Tracking Workflow

#### Coupon-Based Commission
```php
// PaymentController@subscribe
public function subscribe(Request $request)
{
    $validator = Validator::make($request->all(), [
        'plan_id' => 'required|exists:plans,id',
        'coupon_code' => 'nullable|string',
        'payment_method' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = $request->user();
    $plan = Plan::find($request->plan_id);
    $finalAmount = $plan->price;
    $discountAmount = 0;
    $couponUsed = null;

    // Apply coupon if provided
    if ($request->coupon_code) {
        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('expiry_date', '>=', now())
            ->first();

        if ($coupon) {
            $discountAmount = ($plan->price * $coupon->discount_percentage) / 100;
            $finalAmount = $plan->price - $discountAmount;
            $couponUsed = $coupon;
        }
    }

    // Process payment (integrate with payment gateway)
    $transactionId = $this->processPayment($request->payment_method, $finalAmount);

    // Create payment record
    $payment = Payment::create([
        'user_type' => get_class($user) === Employee::class ? 'employee' : 'employer',
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'amount' => $plan->price,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'coupon_code' => $couponUsed?->code,
        'payment_status' => 'completed',
        'transaction_id' => $transactionId,
    ]);

    // Auto-create commission if coupon used
    if ($couponUsed) {
        $commissionAmount = ($finalAmount * 10) / 100; // 10% commission

        CommissionTransaction::create([
            'staff_id' => $couponUsed->staff_id,
            'payment_id' => $payment->id,
            'amount_earned' => $commissionAmount,
            'type' => 'coupon_based',
        ]);
    }

    // Update user's plan
    $user->update(['plan_id' => $plan->id]);

    return response()->json([
        'message' => 'Subscription successful',
        'payment' => $payment,
    ], 201);
}
```

#### Manual Commission
```php
// AdminController@addManualCommission
public function addManualCommission(Request $request)
{
    $admin = $request->user();

    // Authorization check
    if (!in_array($admin->role, ['super_admin', 'plan_upgrade_manager'])) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validator = Validator::make($request->all(), [
        'staff_id' => 'required|exists:admins,id',
        'amount_earned' => 'required|numeric|min:0',
        'payment_id' => 'nullable|exists:payments,id',
        'notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $commission = CommissionTransaction::create([
        'staff_id' => $request->staff_id,
        'payment_id' => $request->payment_id,
        'amount_earned' => $request->amount_earned,
        'type' => 'manual',
    ]);

    return response()->json([
        'message' => 'Commission added successfully',
        'commission' => $commission,
    ], 201);
}
```

---

# Controllers & Services

## Controller Best Practices

### Controller Structure
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExampleController extends Controller
{
    /**
     * GET endpoint
     */
    public function index(Request $request)
    {
        // 1. Authorization check
        // 2. Query database
        // 3. Return JSON response
    }

    /**
     * POST endpoint
     */
    public function store(Request $request)
    {
        // 1. Validate input
        // 2. Authorization check
        // 3. Create resource
        // 4. Return JSON response with 201 status
    }

    /**
     * GET single resource
     */
    public function show(Request $request, $id)
    {
        // 1. Find resource
        // 2. Authorization check
        // 3. Return JSON response
    }

    /**
     * PUT/PATCH endpoint
     */
    public function update(Request $request, $id)
    {
        // 1. Validate input
        // 2. Find resource
        // 3. Authorization check
        // 4. Update resource
        // 5. Return JSON response
    }

    /**
     * DELETE endpoint
     */
    public function destroy(Request $request, $id)
    {
        // 1. Find resource
        // 2. Authorization check
        // 3. Delete resource
        // 4. Return JSON response
    }
}
```

### Validation Examples

```php
// Simple validation
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:employees,email',
    'password' => 'required|string|min:8',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}

// Custom validation messages
$validator = Validator::make($request->all(), [
    'title' => 'required|string',
    'description' => 'required|string',
], [
    'title.required' => 'Job title is mandatory',
    'description.required' => 'Job description is mandatory',
]);

// Conditional validation
$rules = [
    'name' => 'required|string',
];

if ($request->has('email')) {
    $rules['email'] = 'email|unique:employees';
}

$validator = Validator::make($request->all(), $rules);
```

### Response Formatting

```php
// Success response
return response()->json([
    'message' => 'Operation successful',
    'data' => $resource,
], 200);

// Created response
return response()->json([
    'message' => 'Resource created',
    'id' => $resource->id,
], 201);

// Error response
return response()->json([
    'message' => 'Resource not found',
], 404);

// Validation error response
return response()->json([
    'message' => 'Validation failed',
    'errors' => $validator->errors(),
], 422);

// Pagination response
return response()->json([
    'data' => $resources->items(),
    'current_page' => $resources->currentPage(),
    'total' => $resources->total(),
    'per_page' => $resources->perPage(),
], 200);
```

---

# Models & Relationships

## Eloquent Relationships

### One-to-Many: Employer → Jobs
```php
// Employer.php
public function jobs()
{
    return $this->hasMany(Job::class);
}

// Job.php
public function employer()
{
    return $this->belongsTo(Employer::class);
}

// Usage
$employer = Employer::find($id);
$jobs = $employer->jobs; // Get all jobs

$job = Job::with('employer')->find($id);
$companyName = $job->employer->company_name;
```

### Many-to-Many: Employee ↔ Jobs (via JobApplications)
```php
// Employee.php
public function jobApplications()
{
    return $this->hasMany(JobApplication::class);
}

public function appliedJobs()
{
    return $this->hasManyThrough(
        Job::class,
        JobApplication::class,
        'employee_id',
        'id',
        'id',
        'job_id'
    );
}

// Job.php
public function applications()
{
    return $this->hasMany(JobApplication::class);
}

// Usage
$employee = Employee::find($id);
$applications = $employee->jobApplications()->with('job')->get();
```

### One-to-Many: Plan → Employees
```php
// Plan.php
public function employees()
{
    return $this->hasMany(Employee::class);
}

public function employers()
{
    return $this->hasMany(Employer::class);
}

// Employee.php
public function plan()
{
    return $this->belongsTo(Plan::class);
}

// Usage with eager loading
$employee = Employee::with('plan.features')->find($id);
```

### One-to-Many: Plan → PlanFeatures
```php
// Plan.php
public function features()
{
    return $this->hasMany(PlanFeature::class);
}

// PlanFeature.php
public function plan()
{
    return $this->belongsTo(Plan::class);
}

// Usage
$plan = Plan::with('features')->find($id);
foreach ($plan->features as $feature) {
    echo $feature->feature_name . ': ' . $feature->feature_value;
}
```

## Query Scopes

```php
// Job.php
public function scopeFeatured($query)
{
    return $query->where('is_featured', true)
                 ->where('featured_end_date', '>', now());
}

public function scopeByCategory($query, $categoryId)
{
    return $query->where('category_id', $categoryId);
}

public function scopeByLocation($query, $locationId)
{
    return $query->where('location_id', $locationId);
}

// Usage
$featuredJobs = Job::featured()->get();
$techJobs = Job::byCategory($techCategoryId)->get();
$nyJobs = Job::byLocation($nyLocationId)
               ->featured()
               ->latest()
               ->get();
```

## Accessors & Mutators

```php
// Employee.php

// Accessor: Format name
public function getFormattedNameAttribute()
{
    return ucwords($this->name);
}

// Mutator: Auto-hash password
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = bcrypt($value);
}

// Accessor: Get full address
public function getFullAddressAttribute()
{
    if (!$this->address) return null;

    return implode(', ', [
        $this->address['street'] ?? '',
        $this->address['city'] ?? '',
        $this->address['state'] ?? '',
        $this->address['zip'] ?? '',
    ]);
}

// Usage
$employee->formatted_name; // "John Doe"
$employee->password = 'newpass'; // Automatically hashed
$employee->full_address; // "123 Main St, New York, NY, 10001"
```

---

# Middleware & Security

## Custom Middleware

### Rate Limiting
```php
// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    // 60 requests per minute
});

Route::middleware(['throttle:10,1'])->group(function () {
    // 10 requests per minute for login
    Route::post('/auth/login', [AuthController::class, 'login']);
});
```

### CORS Configuration
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'https://yourfrontend.com'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## Security Best Practices

### 1. Input Sanitization
```php
// Always validate and sanitize input
$validator = Validator::make($request->all(), [
    'email' => 'required|email|max:255',
    'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
]);

// Strip tags from user input
$cleanName = strip_tags($request->name);
```

### 2. SQL Injection Prevention
```php
// ❌ NEVER do this
$users = DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ Always use parameter binding
$users = DB::select("SELECT * FROM users WHERE email = ?", [$email]);

// ✅ Or use Eloquent
$users = User::where('email', $email)->get();
```

### 3. Mass Assignment Protection
```php
// Model definition
protected $fillable = ['name', 'email']; // Only these can be mass-assigned
protected $guarded = ['id', 'admin']; // These cannot be mass-assigned

// ❌ Vulnerable
User::create($request->all());

// ✅ Safe
User::create($request->only(['name', 'email']));
```

### 4. File Upload Security
```php
public function uploadCV(Request $request)
{
    $validator = Validator::make($request->all(), [
        'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $file = $request->file('cv_file');

    // Generate unique filename
    $filename = 'cv_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();

    // Store in private directory
    $path = $file->storeAs('cvs', $filename, 'public');

    return response()->json([
        'message' => 'CV uploaded',
        'path' => $path,
    ], 200);
}
```

### 5. XSS Prevention
```php
// In Blade templates, use {{ }} for auto-escaping
{{ $user->name }} // Auto-escaped

{!! $user->bio !!} // Raw output (use carefully)

// In JSON responses, Laravel auto-escapes
return response()->json(['name' => $user->name]); // Safe
```

---

# File Storage & Media Management

## Storage Configuration

### Local Storage (`config/filesystems.php`)
```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

### Directory Structure
```
storage/app/public/
├── cvs/                    # Employee CVs
│   ├── cv_123_1696598400.pdf
│   └── cv_456_1696598500.pdf
├── professional_cvs/       # Professional CVs
│   └── pro_cv_123.pdf
└── media/                  # CMS media
    ├── logo.png
    └── banner.jpg
```

## File Upload Implementation

### CV Upload
```php
public function uploadCV(Request $request)
{
    $validator = Validator::make($request->all(), [
        'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $employee = $request->user();
    $file = $request->file('cv_file');

    // Generate unique filename
    $filename = 'cv_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();

    // Store file
    $path = $file->storeAs('cvs', $filename, 'public');

    // Update employee record
    $employee->update([
        'cv_url' => '/storage/' . $path,
    ]);

    return response()->json([
        'message' => 'CV uploaded successfully',
        'cv_url' => $employee->cv_url,
    ], 200);
}
```

### Media Upload (Admin)
```php
public function upload(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:10240', // 10MB
        'title' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    $path = $file->storeAs('media', $filename, 'public');

    $media = Media::create([
        'title' => $request->title,
        'file_url' => '/storage/' . $path,
        'file_type' => $file->getMimeType(),
        'file_size' => $file->getSize(),
    ]);

    return response()->json([
        'message' => 'Media uploaded successfully',
        'media' => $media,
    ], 201);
}
```

### File Deletion
```php
public function destroy($id)
{
    $media = Media::find($id);

    if (!$media) {
        return response()->json(['message' => 'Media not found'], 404);
    }

    // Delete file from storage
    $filePath = str_replace('/storage/', '', $media->file_url);
    Storage::disk('public')->delete($filePath);

    // Delete database record
    $media->delete();

    return response()->json([
        'message' => 'Media deleted successfully',
    ], 200);
}
```

---

# Third-Party Integrations

## WhatsApp Integration (Placeholder)

### Queue Job Implementation
```php
// app/Jobs/SendWhatsAppNotification.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;

    public function __construct($phoneNumber, $message)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
    }

    public function handle()
    {
        // TODO: Integrate with WhatsApp Business API
        // Example using Twilio or similar service

        try {
            $response = Http::post(env('WHATSAPP_API_URL'), [
                'to' => $this->phoneNumber,
                'message' => $this->message,
                'api_key' => env('WHATSAPP_API_KEY'),
            ]);

            if ($response->successful()) {
                \Log::info("WhatsApp sent to {$this->phoneNumber}");
            } else {
                \Log::error("WhatsApp failed: " . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error("WhatsApp error: " . $e->getMessage());
        }
    }
}
```

### Usage in Controllers
```php
// Dispatch asynchronously
dispatch(new SendWhatsAppNotification($employee->mobile, $message));

// Or use helper
\App\Jobs\SendWhatsAppNotification::dispatch($employer->contact, $message);
```

## Payment Gateway Integration (Placeholder)

### Stripe Example
```php
// composer require stripe/stripe-php

public function processPayment($amount)
{
    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

    try {
        $charge = \Stripe\Charge::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => 'usd',
            'source' => $request->stripe_token,
            'description' => 'Plan subscription',
        ]);

        return $charge->id; // Transaction ID
    } catch (\Stripe\Exception\CardException $e) {
        throw new \Exception('Payment failed: ' . $e->getError()->message);
    }
}
```

---

# Testing Strategy

## PHPUnit Configuration

### Test Structure
```
tests/
├── Feature/
│   ├── AuthTest.php
│   ├── EmployeeTest.php
│   ├── EmployerTest.php
│   └── AdminTest.php
└── Unit/
    ├── EmployeeModelTest.php
    └── JobModelTest.php
```

## Feature Test Example

```php
// tests/Feature/AuthTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register/employee-step1', [
            'email' => 'test@example.com',
            'mobile' => '+1234567890',
            'name' => 'Test User',
            'password' => 'Password123!',
            'gender' => 'M',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'tempToken']);

        $this->assertDatabaseHas('employees', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_employee_can_login()
    {
        $employee = Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user_type', 'user']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'wrong@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }
}
```

## Unit Test Example

```php
// tests/Unit/EmployeeModelTest.php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_belongs_to_plan()
    {
        $plan = Plan::factory()->create();
        $employee = Employee::factory()->create(['plan_id' => $plan->id]);

        $this->assertInstanceOf(Plan::class, $employee->plan);
        $this->assertEquals($plan->id, $employee->plan->id);
    }

    public function test_password_is_hashed()
    {
        $employee = Employee::factory()->create([
            'password' => 'PlainPassword',
        ]);

        $this->assertNotEquals('PlainPassword', $employee->password);
        $this->assertTrue(\Hash::check('PlainPassword', $employee->password));
    }
}
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter test_employee_can_login
```

---

# Deployment Guide

## Production Environment Setup

### 1. Server Requirements
```
- PHP 7.3+ or 8.0+
- MySQL 5.7+ or 8.0+
- Composer
- Nginx or Apache
- SSL Certificate (Let's Encrypt recommended)
```

### 2. Environment Configuration

```bash
# .env.production
APP_NAME="Job Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourjobportal.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=job_portal_prod
DB_USERNAME=db_user
DB_PASSWORD=strong_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Deployment Steps

```bash
# 1. Clone repository
git clone <repo-url> /var/www/job-portal-api
cd /var/www/job-portal-api

# 2. Install dependencies
composer install --optimize-autoloader --no-dev

# 3. Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 4. Environment setup
cp .env.production .env
php artisan key:generate

# 5. Database migration
php artisan migrate --force

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Storage link
php artisan storage:link

# 8. Queue worker (supervisor)
sudo supervisorctl restart laravel-worker
```

### 4. Nginx Configuration

```nginx
server {
    listen 80;
    server_name api.yourjobportal.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.yourjobportal.com;
    root /var/www/job-portal-api/public;

    ssl_certificate /etc/letsencrypt/live/api.yourjobportal.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourjobportal.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Supervisor Configuration (Queue Worker)

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/job-portal-api/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/job-portal-api/storage/logs/worker.log
```

---

# Performance Optimization

## Database Optimization

### 1. Eager Loading
```php
// ❌ N+1 Query Problem
$jobs = Job::all();
foreach ($jobs as $job) {
    echo $job->employer->company_name; // Fires N queries
}

// ✅ Eager Loading
$jobs = Job::with('employer')->get();
foreach ($jobs as $job) {
    echo $job->employer->company_name; // Single query
}

// ✅ Multiple relationships
$jobs = Job::with(['employer', 'location', 'category'])->get();
```

### 2. Query Optimization
```php
// ❌ Retrieve all columns
$employees = Employee::all();

// ✅ Select only needed columns
$employees = Employee::select('id', 'name', 'email')->get();

// ✅ Use chunking for large datasets
Employee::chunk(100, function ($employees) {
    foreach ($employees as $employee) {
        // Process each employee
    }
});
```

### 3. Caching
```php
// Cache job listings for 10 minutes
$jobs = Cache::remember('featured_jobs', 600, function () {
    return Job::with('employer')
              ->where('is_featured', true)
              ->get();
});

// Clear cache when job is created/updated
Cache::forget('featured_jobs');
```

## Response Optimization

### 1. Pagination
```php
// Always paginate large datasets
$jobs = Job::with('employer')->paginate(20);

return response()->json([
    'jobs' => $jobs->items(),
    'total' => $jobs->total(),
    'current_page' => $jobs->currentPage(),
], 200);
```

### 2. API Resources (Optional)
```php
// app/Http/Resources/JobResource.php
class JobResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'employer' => [
                'name' => $this->employer->company_name,
                'id' => $this->employer->id,
            ],
        ];
    }
}

// Usage
return JobResource::collection($jobs);
```

---

# Troubleshooting & Debugging

## Common Issues

### 1. Authentication Issues

**Problem:** Token not working
```bash
# Check token in database
SELECT * FROM personal_access_tokens WHERE tokenable_id = <user_id>;

# Clear expired tokens
php artisan sanctum:prune-expired
```

**Problem:** CORS errors
```bash
# Clear config cache
php artisan config:clear

# Verify CORS settings in config/cors.php
```

### 2. Database Issues

**Problem:** Foreign key constraint error
```sql
-- Check constraints
SHOW CREATE TABLE job_applications;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;
-- Run migration
SET FOREIGN_KEY_CHECKS=1;
```

**Problem:** Migration fails
```bash
# Rollback last migration
php artisan migrate:rollback --step=1

# Fresh migration (CAUTION: Deletes all data)
php artisan migrate:fresh

# Check migration status
php artisan migrate:status
```

### 3. File Upload Issues

**Problem:** Files not uploading
```bash
# Check storage permissions
ls -la storage/app/public

# Recreate symlink
rm public/storage
php artisan storage:link

# Check upload max filesize in php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

## Debugging Tools

### 1. Laravel Telescope (Optional)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Access at: /telescope
```

### 2. Query Debugging
```php
// Enable query log
DB::enableQueryLog();

// Run queries
$employees = Employee::with('plan')->get();

// Dump queries
dd(DB::getQueryLog());
```

### 3. Error Logging
```php
// Log custom messages
\Log::info('User logged in', ['user_id' => $user->id]);
\Log::error('Payment failed', ['error' => $e->getMessage()]);
\Log::debug('Debug info', $data);

// View logs
tail -f storage/logs/laravel.log
```

---

## Additional Resources

### Official Documentation
- Laravel 8: https://laravel.com/docs/8.x
- Laravel Sanctum: https://laravel.com/docs/8.x/sanctum
- Eloquent ORM: https://laravel.com/docs/8.x/eloquent

### Development Tools
- Laravel Debugbar: https://github.com/barryvdh/laravel-debugbar
- Laravel IDE Helper: https://github.com/barryvdh/laravel-ide-helper
- Postman Collection: (Create and share with team)

---

**End of Backend Documentation**

For questions, issues, or contributions, please contact the development team or create an issue in the project repository.

**Last Updated:** 2025-10-06
**Version:** 1.0
**Maintained By:** Backend Development Team

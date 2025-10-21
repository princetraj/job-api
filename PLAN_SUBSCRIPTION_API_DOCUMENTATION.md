# Employee Plan Subscription System - API Documentation

## Overview

This document provides comprehensive documentation for the newly implemented employee plan subscription system. The system allows employees to be automatically assigned a default plan upon registration and upgrade to premium plans.

## Database Changes

### New Migrations

1. **add_is_default_to_plans_table** - Adds `is_default` field to plans table
2. **add_plan_subscription_fields_to_employees_table** - Adds subscription tracking fields to employees table:
   - `plan_started_at` (timestamp)
   - `plan_expires_at` (timestamp)
   - `plan_is_active` (boolean)

3. **create_employee_plan_subscriptions_table** - Creates subscription history table:
   - `id` (UUID, primary key)
   - `employee_id` (UUID, foreign key to employees)
   - `plan_id` (UUID, foreign key to plans)
   - `payment_id` (UUID, nullable, foreign key to payments)
   - `started_at` (timestamp)
   - `expires_at` (timestamp, nullable)
   - `status` (enum: 'active', 'expired', 'cancelled')
   - `is_default` (boolean)
   - `created_at`, `updated_at` (timestamps)

### Models Updated

1. **Plan Model** - Added:
   - `is_default` field
   - `subscriptions()` relationship
   - `scopeDefault()` method
   - `scopeNonDefault()` method
   - `scopeOfType()` method
   - `getDefaultPlan()` static method
   - `isDefault()` method

2. **Employee Model** - Added:
   - Plan subscription fields
   - `planSubscriptions()` relationship
   - `currentSubscription()` relationship
   - `hasActivePlan()` method
   - `isPlanExpired()` method

3. **EmployeePlanSubscription Model** - New model with:
   - Relationships to Employee, Plan, and Payment
   - `isActive()` method
   - `isExpired()` method
   - `scopeActive()` query scope
   - `scopeExpired()` query scope

4. **PlanFeature Model** - Fixed:
   - Added UUID auto-generation

## Default Plans Seeder

**File:** `database/seeders/DefaultPlanSeeder.php`

Creates 3 default employee plans:
- **Free Plan** (default, $0, 365 days)
- **Premium Plan** ($29.99, 30 days)
- **Professional Plan** ($79.99, 90 days)

Run with: `php artisan db:seed --class=DefaultPlanSeeder`

---

## API Endpoints

### 1. Employee Registration (Modified)

**Endpoint:** `POST /api/v1/auth/register/employee-step1`

**Changes:** Now automatically assigns the default plan to new employees.

**Request Body:**
```json
{
    "email": "employee@example.com",
    "mobile": "1234567890",
    "name": "John Doe",
    "password": "password123",
    "gender": "M"
}
```

**Response:**
```json
{
    "message": "Step 1 complete. Default plan assigned.",
    "tempToken": "1|abc123...",
    "plan": {
        "name": "Free Plan",
        "expires_at": "2026-10-18 12:00:00"
    }
}
```

---

### 2. Get Current Plan

**Endpoint:** `GET /api/v1/employee/plan/current`

**Auth:** Required (Bearer token)

**Description:** Retrieves the current active plan for the authenticated employee.

**Response:**
```json
{
    "plan": {
        "id": "uuid",
        "name": "Free Plan",
        "description": "Basic features for job seekers to get started",
        "price": "0.00",
        "features": [
            {
                "id": "uuid",
                "feature_name": "Job Applications",
                "feature_value": "5 per month"
            },
            {
                "id": "uuid",
                "feature_name": "Profile Views",
                "feature_value": "Basic visibility"
            }
        ],
        "is_default": true,
        "started_at": "2025-10-18T12:00:00.000000Z",
        "expires_at": "2026-10-18T12:00:00.000000Z",
        "is_active": true,
        "is_expired": false,
        "days_remaining": 365
    }
}
```

---

### 3. Get Available Plans for Upgrade

**Endpoint:** `GET /api/v1/employee/plan/available`

**Auth:** Required (Bearer token)

**Description:** Retrieves all non-default employee plans that can be upgraded to.

**Response:**
```json
{
    "plans": [
        {
            "id": "uuid",
            "name": "Premium Plan",
            "description": "Enhanced features for serious job seekers",
            "price": "29.99",
            "validity_days": 30,
            "features": [
                {
                    "id": "uuid",
                    "feature_name": "Job Applications",
                    "feature_value": "Unlimited"
                },
                {
                    "id": "uuid",
                    "feature_name": "Profile Views",
                    "feature_value": "High visibility"
                }
            ],
            "is_current": false
        },
        {
            "id": "uuid",
            "name": "Professional Plan",
            "description": "Ultimate package for career advancement",
            "price": "79.99",
            "validity_days": 90,
            "features": [...],
            "is_current": false
        }
    ]
}
```

---

### 4. Upgrade Plan

**Endpoint:** `POST /api/v1/employee/plan/upgrade`

**Auth:** Required (Bearer token)

**Description:** Upgrades the employee to a new plan.

**Request Body:**
```json
{
    "plan_id": "uuid-of-new-plan",
    "payment_id": "uuid-of-payment" // Optional, can be null
}
```

**Response (Success):**
```json
{
    "message": "Plan upgraded successfully",
    "plan": {
        "name": "Premium Plan",
        "started_at": "2025-10-18 12:00:00",
        "expires_at": "2025-11-17 12:00:00"
    },
    "subscription_id": "uuid"
}
```

**Error Responses:**

Invalid plan type:
```json
{
    "message": "Invalid plan type"
}
```

Cannot downgrade to default:
```json
{
    "message": "Cannot upgrade to default plan"
}
```

Already subscribed:
```json
{
    "message": "Already subscribed to this plan"
}
```

---

### 5. Get Plan Subscription History

**Endpoint:** `GET /api/v1/employee/plan/history`

**Auth:** Required (Bearer token)

**Description:** Retrieves the complete subscription history for the employee.

**Response:**
```json
{
    "history": [
        {
            "id": "uuid",
            "plan": {
                "name": "Premium Plan",
                "price": "29.99"
            },
            "started_at": "2025-10-18T12:00:00.000000Z",
            "expires_at": "2025-11-17T12:00:00.000000Z",
            "status": "active",
            "is_default": false
        },
        {
            "id": "uuid",
            "plan": {
                "name": "Free Plan",
                "price": "0.00"
            },
            "started_at": "2025-09-18T12:00:00.000000Z",
            "expires_at": "2026-09-18T12:00:00.000000Z",
            "status": "cancelled",
            "is_default": true
        }
    ]
}
```

---

### 6. Get All Plans (Public)

**Endpoint:** `GET /api/v1/plans/`

**Auth:** Not required

**Query Parameters:**
- `type` (optional): Filter by type ('employee' or 'employer')

**Description:** Retrieves all available plans (public endpoint).

**Response:**
```json
{
    "plans": [
        {
            "id": "uuid",
            "name": "Free Plan",
            "description": "Basic features for job seekers to get started",
            "type": "employee",
            "price": "0.00",
            "validity_days": 365,
            "is_default": true,
            "features": [...]
        },
        ...
    ]
}
```

---

## Modified Endpoints

### Employee Profile

**Endpoint:** `GET /api/v1/employee/profile`

**Changes:** Now includes plan information with features.

**Response:**
```json
{
    "user": {
        "id": "uuid",
        "email": "employee@example.com",
        "name": "John Doe",
        ...
        "plan_id": "uuid",
        "plan_started_at": "2025-10-18T12:00:00.000000Z",
        "plan_expires_at": "2026-10-18T12:00:00.000000Z",
        "plan_is_active": true
    },
    "plan": {
        "id": "uuid",
        "name": "Free Plan",
        "features": [...]
    }
}
```

---

## Testing Guide

### 1. Test Employee Registration with Auto Plan Assignment

```bash
curl -X POST http://your-app-url/api/v1/auth/register/employee-step1 \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "mobile": "1234567890",
    "name": "Test User",
    "password": "password123",
    "gender": "M"
  }'
```

### 2. Test Get Current Plan

```bash
curl -X GET http://your-app-url/api/v1/employee/plan/current \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Test Get Available Plans

```bash
curl -X GET http://your-app-url/api/v1/employee/plan/available \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Test Upgrade Plan

```bash
curl -X POST http://your-app-url/api/v1/employee/plan/upgrade \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": "PLAN_UUID"
  }'
```

### 5. Test Get Plan History

```bash
curl -X GET http://your-app-url/api/v1/employee/plan/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Frontend Integration Guide

### React Admin Panel Integration

#### 1. Plan Management Page

Create a plan management interface at `admin-panel/src/pages/Plans.jsx`:

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Plans = () => {
  const [plans, setPlans] = useState([]);

  useEffect(() => {
    fetchPlans();
  }, []);

  const fetchPlans = async () => {
    const response = await axios.get('/api/v1/plans/', {
      params: { type: 'employee' }
    });
    setPlans(response.data.plans);
  };

  return (
    <div>
      <h1>Employee Plans</h1>
      {plans.map(plan => (
        <div key={plan.id} className="plan-card">
          <h3>{plan.name}</h3>
          <p>{plan.description}</p>
          <p>${plan.price} / {plan.validity_days} days</p>
          {plan.is_default && <span className="badge">Default</span>}
          <ul>
            {plan.features.map(feature => (
              <li key={feature.id}>
                {feature.feature_name}: {feature.feature_value}
              </li>
            ))}
          </ul>
        </div>
      ))}
    </div>
  );
};

export default Plans;
```

### Next.js Employee Frontend Integration

#### 1. Current Plan Display

Create `job-portal-frontend/components/PlanCard.jsx`:

```jsx
import { useEffect, useState } from 'react';
import axios from 'axios';

export default function PlanCard() {
  const [plan, setPlan] = useState(null);

  useEffect(() => {
    fetchCurrentPlan();
  }, []);

  const fetchCurrentPlan = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get('/api/v1/employee/plan/current', {
        headers: { Authorization: `Bearer ${token}` }
      });
      setPlan(response.data.plan);
    } catch (error) {
      console.error('Error fetching plan:', error);
    }
  };

  if (!plan) return <div>Loading...</div>;

  return (
    <div className="plan-card">
      <h3>Current Plan: {plan.name}</h3>
      <p>Expires: {new Date(plan.expires_at).toLocaleDateString()}</p>
      <p>Days Remaining: {plan.days_remaining}</p>
      {plan.is_expired && <span className="badge-danger">Expired</span>}
    </div>
  );
}
```

#### 2. Upgrade Plans Page

Create `job-portal-frontend/pages/upgrade-plan.jsx`:

```jsx
import { useEffect, useState } from 'react';
import axios from 'axios';
import { useRouter } from 'next/router';

export default function UpgradePlan() {
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  useEffect(() => {
    fetchAvailablePlans();
  }, []);

  const fetchAvailablePlans = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get('/api/v1/employee/plan/available', {
        headers: { Authorization: `Bearer ${token}` }
      });
      setPlans(response.data.plans);
    } catch (error) {
      console.error('Error fetching plans:', error);
    }
  };

  const handleUpgrade = async (planId) => {
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      const response = await axios.post(
        '/api/v1/employee/plan/upgrade',
        { plan_id: planId },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      alert(response.data.message);
      router.push('/dashboard');
    } catch (error) {
      alert(error.response?.data?.message || 'Upgrade failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="upgrade-plans-page">
      <h1>Upgrade Your Plan</h1>
      <div className="plans-grid">
        {plans.map(plan => (
          <div key={plan.id} className={`plan-card ${plan.is_current ? 'current' : ''}`}>
            <h3>{plan.name}</h3>
            <p className="price">${plan.price}</p>
            <p className="validity">{plan.validity_days} days</p>
            <p>{plan.description}</p>
            <ul className="features">
              {plan.features.map(feature => (
                <li key={feature.id}>
                  <strong>{feature.feature_name}:</strong> {feature.feature_value}
                </li>
              ))}
            </ul>
            {!plan.is_current && (
              <button
                onClick={() => handleUpgrade(plan.id)}
                disabled={loading}
                className="btn-upgrade"
              >
                Upgrade Now
              </button>
            )}
            {plan.is_current && <span className="badge">Current Plan</span>}
          </div>
        ))}
      </div>
    </div>
  );
}
```

#### 3. Navigation Menu Update

Add link to upgrade page in your navigation:

```jsx
{/* In your navigation component */}
<Link href="/upgrade-plan">
  <a className="nav-link">Upgrade Plan</a>
</Link>
```

---

## Implementation Summary

### Backend (Laravel API) - COMPLETED

1. Database schema changes via 3 migrations
2. Updated 3 existing models (Plan, Employee, PlanFeature)
3. Created 1 new model (EmployeePlanSubscription)
4. Modified AuthController to auto-assign default plan
5. Added 4 new endpoints to EmployeeController
6. Created default plan seeder with 3 plans
7. Added 4 new API routes

### Files Created/Modified

**New Files:**
- `database/migrations/2025_10_18_113155_add_is_default_to_plans_table.php`
- `database/migrations/2025_10_18_113405_add_plan_subscription_fields_to_employees_table.php`
- `database/migrations/2025_10_18_113412_create_employee_plan_subscriptions_table.php`
- `app/Models/EmployeePlanSubscription.php`
- `database/seeders/DefaultPlanSeeder.php`

**Modified Files:**
- `app/Models/Plan.php`
- `app/Models/Employee.php`
- `app/Models/PlanFeature.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/EmployeeController.php`
- `routes/api.php`

### Frontend - PENDING

The frontend integration examples are provided above for:
1. React Admin Panel (admin-panel)
2. Next.js Employee Frontend (job-portal-frontend)

---

## Next Steps

1. **Payment Integration:** Integrate the payment gateway with the upgrade plan functionality
2. **Email Notifications:** Send email notifications when plans are upgraded or about to expire
3. **Plan Expiry Cron Job:** Create a scheduled task to mark expired plans
4. **Admin Dashboard:** Add analytics for plan subscriptions in the admin panel
5. **Frontend Implementation:** Implement the UI components as shown in the integration guide above

---

## Support

For questions or issues, please refer to:
- Main API Documentation: `ADMIN_PANEL_API_DOCUMENTATION.md`
- Laravel Documentation: https://laravel.com/docs
- Contact: Your development team

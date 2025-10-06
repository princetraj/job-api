<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployerController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\MediaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::prefix('v1/auth')->group(function () {
    // Employee Registration (Multi-step)
    Route::post('/register/employee-step1', [AuthController::class, 'employeeRegisterStep1']);
    Route::post('/register/employee-step2', [AuthController::class, 'employeeRegisterStep2'])->middleware('auth:sanctum');
    Route::post('/register/employee-final', [AuthController::class, 'employeeRegisterFinal'])->middleware('auth:sanctum');

    // Employer Registration
    Route::post('/register/employer', [AuthController::class, 'employerRegister']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Employee Routes
Route::prefix('v1/employee')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [EmployeeController::class, 'getProfile']);
    Route::put('/profile/update', [EmployeeController::class, 'updateProfile']);

    // Job Search & Application
    Route::get('/jobs/search', [EmployeeController::class, 'searchJobs']);
    Route::post('/jobs/{jobId}/apply', [EmployeeController::class, 'applyForJob']);
    Route::get('/jobs/applied', [EmployeeController::class, 'getAppliedJobs']);

    // Shortlist
    Route::post('/jobs/shortlist', [EmployeeController::class, 'shortlistJob']);
    Route::get('/jobs/shortlisted', [EmployeeController::class, 'getShortlistedJobs']);
    Route::delete('/jobs/shortlist/{id}', [EmployeeController::class, 'removeShortlist']);

    // CV Management
    Route::get('/cv/generate', [EmployeeController::class, 'generateCV']);
    Route::post('/cv/upload', [EmployeeController::class, 'uploadCV']);
    Route::post('/cv/request-professional', [EmployeeController::class, 'requestProfessionalCV']);
    Route::get('/cv/requests', [EmployeeController::class, 'getMyCVRequests']);
    Route::get('/cv/requests/{requestId}', [EmployeeController::class, 'getCVRequestStatus']);
});

// Employer Routes
Route::prefix('v1/employer')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [EmployerController::class, 'getProfile']);
    Route::put('/profile/update', [EmployerController::class, 'updateProfile']);

    // Job Management
    Route::post('/jobs', [EmployerController::class, 'createJob']);
    Route::get('/jobs/{jobId}', [EmployerController::class, 'getJob']);
    Route::put('/jobs/{jobId}', [EmployerController::class, 'updateJob']);
    Route::delete('/jobs/{jobId}', [EmployerController::class, 'deleteJob']);

    // Application Management
    Route::get('/jobs/{jobId}/applications', [EmployerController::class, 'getJobApplications']);
    Route::put('/applications/{appId}/status', [EmployerController::class, 'updateApplicationStatus']);
});

// Admin Routes (Protected with auth:sanctum)
Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function () {
    // Admin Profile & Dashboard
    Route::get('/profile', [AdminController::class, 'getProfile']);
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);

    // Admin Management (Super Admin only)
    Route::get('/admins', [AdminController::class, 'getAdmins']);
    Route::get('/admins/{id}', [AdminController::class, 'getAdmin']);
    Route::post('/admins', [AdminController::class, 'createAdmin']);
    Route::put('/admins/{id}', [AdminController::class, 'updateAdmin']);
    Route::delete('/admins/{id}', [AdminController::class, 'deleteAdmin']);

    // Employee Management
    Route::get('/employees', [AdminController::class, 'getEmployees']);
    Route::get('/employees/{id}', [AdminController::class, 'getEmployee']);
    Route::put('/employees/{id}', [AdminController::class, 'updateEmployee']);
    Route::delete('/employees/{id}', [AdminController::class, 'deleteEmployee']);

    // Employer Management
    Route::get('/employers', [AdminController::class, 'getEmployers']);
    Route::get('/employers/{id}', [AdminController::class, 'getEmployer']);
    Route::put('/employers/{id}', [AdminController::class, 'updateEmployer']);
    Route::delete('/employers/{id}', [AdminController::class, 'deleteEmployer']);

    // Job Management
    Route::get('/jobs', [AdminController::class, 'getJobs']);

    // Coupon Management
    Route::post('/coupons', [AdminController::class, 'createCoupon']);
    Route::get('/coupons', [AdminController::class, 'getCoupons']);

    // Commission Management
    Route::post('/commissions/manual', [AdminController::class, 'addManualCommission']);
    Route::get('/commissions/all', [AdminController::class, 'getAllCommissions']);
    Route::get('/commissions/my', [AdminController::class, 'getStaffCommissions']);

    // CV Request Management
    Route::get('/cv-requests', [AdminController::class, 'getCVRequests']);
    Route::put('/cv-requests/{id}/status', [AdminController::class, 'updateCVRequestStatus']);

    // Content Management
    Route::get('/content', [ContentController::class, 'index']);
    Route::post('/content', [ContentController::class, 'store']);
    Route::put('/content/{id}', [ContentController::class, 'update']);
    Route::delete('/content/{id}', [ContentController::class, 'destroy']);

    // Media Management
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::put('/media/{id}', [MediaController::class, 'update']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);
});

// Plan Routes
Route::prefix('v1/plans')->group(function () {
    // Public
    Route::get('/', [PlanController::class, 'getAllPlans']);
    Route::get('/{id}', [PlanController::class, 'getPlan']);

    // Admin only (protected)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PlanController::class, 'createPlan']);
        Route::put('/{id}', [PlanController::class, 'updatePlan']);
        Route::delete('/{id}', [PlanController::class, 'deletePlan']);
        Route::post('/{planId}/features', [PlanController::class, 'addPlanFeature']);
        Route::delete('/features/{featureId}', [PlanController::class, 'removePlanFeature']);
    });
});

// Catalog Routes (Industries, Locations, Job Categories)
Route::prefix('v1/catalogs')->group(function () {
    // Industries
    Route::get('/industries', [CatalogController::class, 'getIndustries']);
    Route::post('/industries', [CatalogController::class, 'createIndustry'])->middleware('auth:sanctum');
    Route::put('/industries/{id}', [CatalogController::class, 'updateIndustry'])->middleware('auth:sanctum');
    Route::delete('/industries/{id}', [CatalogController::class, 'deleteIndustry'])->middleware('auth:sanctum');

    // Locations
    Route::get('/locations', [CatalogController::class, 'getLocations']);
    Route::post('/locations', [CatalogController::class, 'createLocation'])->middleware('auth:sanctum');
    Route::put('/locations/{id}', [CatalogController::class, 'updateLocation'])->middleware('auth:sanctum');
    Route::delete('/locations/{id}', [CatalogController::class, 'deleteLocation'])->middleware('auth:sanctum');

    // Job Categories
    Route::get('/categories', [CatalogController::class, 'getJobCategories']);
    Route::post('/categories', [CatalogController::class, 'createJobCategory'])->middleware('auth:sanctum');
    Route::put('/categories/{id}', [CatalogController::class, 'updateJobCategory'])->middleware('auth:sanctum');
    Route::delete('/categories/{id}', [CatalogController::class, 'deleteJobCategory'])->middleware('auth:sanctum');
});

// Payment & Subscription Routes
Route::prefix('v1/payments')->middleware('auth:sanctum')->group(function () {
    Route::post('/subscribe', [\App\Http\Controllers\Api\PaymentController::class, 'subscribe']);
    Route::post('/verify', [\App\Http\Controllers\Api\PaymentController::class, 'verifyPayment']);
    Route::get('/history', [\App\Http\Controllers\Api\PaymentController::class, 'getPaymentHistory']);
});

// Coupon Routes
Route::prefix('v1/coupons')->group(function () {
    Route::post('/validate', [\App\Http\Controllers\Api\PaymentController::class, 'validateCoupon']);
});

// Public Job Routes
Route::prefix('v1/jobs')->group(function () {
    Route::get('/search', [EmployeeController::class, 'searchJobs']);
});

// Public Content Routes
Route::prefix('v1/content')->group(function () {
    Route::get('/', [ContentController::class, 'index']);
    Route::get('/{identifier}', [ContentController::class, 'show']);
});

// Public Media Routes
Route::prefix('v1/media')->group(function () {
    Route::get('/{id}', [MediaController::class, 'show']);
});

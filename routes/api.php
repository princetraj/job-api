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
    Route::post('/jobs/{jobId}/view-contact', [EmployeeController::class, 'viewEmployerContact']);
    Route::get('/jobs/applied', [EmployeeController::class, 'getAppliedJobs']);

    // Shortlist
    Route::post('/jobs/shortlist', [EmployeeController::class, 'shortlistJob']);
    Route::get('/jobs/shortlisted', [EmployeeController::class, 'getShortlistedJobs']);
    Route::delete('/jobs/shortlist/{id}', [EmployeeController::class, 'removeShortlist']);

    // Contact Viewed Jobs
    Route::get('/jobs/contact-viewed', [EmployeeController::class, 'getContactViewedJobs']);

    // CV Management (Legacy endpoints)
    Route::get('/cv/generate', [EmployeeController::class, 'generateCV']);
    Route::post('/cv/upload', [EmployeeController::class, 'uploadCV']);
    Route::post('/cv/request-professional', [EmployeeController::class, 'requestProfessionalCV']);
    Route::get('/cv/requests', [EmployeeController::class, 'getMyCVRequests']);
    Route::get('/cv/requests/{requestId}', [EmployeeController::class, 'getCVRequestStatus']);

    // CV Management (New - Multiple CVs)
    Route::get('/cvs', [EmployeeController::class, 'getAllCVs']);
    Route::post('/cvs/upload', [EmployeeController::class, 'uploadCVWithTitle']);
    Route::post('/cvs/create', [EmployeeController::class, 'createCVWithProfile']);
    Route::put('/cvs/{cvId}/set-active', [EmployeeController::class, 'setActiveCVById']);
    Route::delete('/cvs/{cvId}', [EmployeeController::class, 'deleteCVById']);
    Route::get('/cvs/{cvId}/download', [EmployeeController::class, 'downloadCVById']);

    // Plan Management
    Route::get('/plan/current', [EmployeeController::class, 'getCurrentPlan']);
    Route::get('/plan/available', [EmployeeController::class, 'getAvailablePlans']);
    Route::post('/plan/upgrade', [EmployeeController::class, 'upgradePlan']);
    Route::get('/plan/history', [EmployeeController::class, 'getPlanHistory']);

    // Profile Photo Management
    Route::post('/profile/photo/upload', [EmployeeController::class, 'uploadProfilePhoto']);
    Route::get('/profile/photo/status', [EmployeeController::class, 'getProfilePhotoStatus']);
});

// Employer Routes
Route::prefix('v1/employer')->middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/profile', [EmployerController::class, 'getProfile']);
    Route::put('/profile/update', [EmployerController::class, 'updateProfile']);

    // Job Management
    Route::get('/jobs', [EmployerController::class, 'getAllJobs']);
    Route::post('/jobs', [EmployerController::class, 'createJob']);
    Route::get('/jobs/{jobId}', [EmployerController::class, 'getJob']);
    Route::put('/jobs/{jobId}', [EmployerController::class, 'updateJob']);
    Route::delete('/jobs/{jobId}', [EmployerController::class, 'deleteJob']);

    // Application Management
    Route::get('/applications', [EmployerController::class, 'getAllApplications']);
    Route::get('/jobs/{jobId}/applications', [EmployerController::class, 'getJobApplications']);
    Route::put('/applications/{appId}/status', [EmployerController::class, 'updateApplicationStatus']);
    Route::post('/applications/{appId}/view-contact', [EmployerController::class, 'viewApplicationContactDetails']);
    Route::get('/employees/{employeeId}/cv/download', [EmployerController::class, 'downloadEmployeeCV']);

    // Plan Management
    Route::get('/plan/current', [EmployerController::class, 'getCurrentPlan']);
    Route::get('/plan/available', [EmployerController::class, 'getAvailablePlans']);
    Route::post('/plan/upgrade', [EmployerController::class, 'upgradePlan']);
    Route::get('/plan/history', [EmployerController::class, 'getPlanHistory']);
});

// Admin Routes (Protected with auth:sanctum and admin middleware)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Admin Profile & Dashboard
    Route::get('/profile', [AdminController::class, 'getProfile']);
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);

    // Admin Management (Super Admin only)
    Route::get('/admins', [AdminController::class, 'getAdmins']);
    Route::get('/admins/{id}', [AdminController::class, 'getAdmin']);
    Route::post('/admins', [AdminController::class, 'createAdmin']);
    Route::put('/admins/{id}', [AdminController::class, 'updateAdmin']);
    Route::delete('/admins/{id}', [AdminController::class, 'deleteAdmin']);
    Route::put('/admins/{staffId}/assign-manager', [AdminController::class, 'assignStaffToManager']);
    Route::get('/managers', [AdminController::class, 'getManagers']);

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

    // Plan Upgrade Management
    Route::post('/employees/{employeeId}/upgrade-plan', [AdminController::class, 'upgradeEmployeePlan']);
    Route::post('/employers/{employerId}/upgrade-plan', [AdminController::class, 'upgradeEmployerPlan']);

    // Profile Photo Approval
    Route::get('/profile-photos', [AdminController::class, 'getProfilePhotos']); // New: with status filter
    Route::get('/profile-photos/pending', [AdminController::class, 'getPendingProfilePhotos']); // Backward compatibility
    Route::put('/profile-photos/{employeeId}/status', [AdminController::class, 'updateProfilePhotoStatus']);

    // Plan Orders & Transactions
    Route::get('/plan-orders', [AdminController::class, 'getPlanOrders']);
    Route::get('/plan-orders/{id}', [AdminController::class, 'getPlanOrder']);
    Route::get('/payment-transactions', [AdminController::class, 'getPaymentTransactions']);
    Route::get('/payment-transactions/{id}', [AdminController::class, 'getPaymentTransaction']);
    Route::get('/payment-stats', [AdminController::class, 'getPaymentStats']);

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

    // Skills
    Route::get('/skills', [CatalogController::class, 'getSkills']);
    Route::post('/skills', [CatalogController::class, 'createSkill'])->middleware('auth:sanctum');
    Route::put('/skills/{id}', [CatalogController::class, 'updateSkill'])->middleware('auth:sanctum');
    Route::delete('/skills/{id}', [CatalogController::class, 'deleteSkill'])->middleware('auth:sanctum');
});

// Payment & Subscription Routes
Route::prefix('v1/payments')->middleware('auth:sanctum')->group(function () {
    Route::post('/subscribe', [\App\Http\Controllers\Api\PaymentController::class, 'subscribe']);
    Route::post('/verify', [\App\Http\Controllers\Api\PaymentController::class, 'verifyPayment']);
    Route::get('/history', [\App\Http\Controllers\Api\PaymentController::class, 'getPaymentHistory']);

    // Razorpay Integration
    Route::post('/razorpay/create-order', [\App\Http\Controllers\Api\PaymentController::class, 'createRazorpayOrder']);
    Route::post('/razorpay/verify', [\App\Http\Controllers\Api\PaymentController::class, 'verifyRazorpayPayment']);
    Route::get('/orders/{orderId}', [\App\Http\Controllers\Api\PaymentController::class, 'getOrderDetails']);
    Route::get('/transactions', [\App\Http\Controllers\Api\PaymentController::class, 'getTransactionHistory']);
});

// Coupon Routes
Route::prefix('v1/coupons')->group(function () {
    Route::post('/validate', [\App\Http\Controllers\Api\PaymentController::class, 'validateCoupon']);
});

// Public Job Routes (with optional authentication)
Route::prefix('v1/jobs')->middleware('auth.optional')->group(function () {
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Plan;
use App\Models\EmployerPlanSubscription;
use App\Models\CV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployerController extends Controller
{
    /**
     * Get employer profile
     */
    public function getProfile(Request $request)
    {
        $employer = $request->user()->load('plan.features', 'industry');

        return response()->json([
            'user' => $employer,
            'plan' => $employer->plan,
        ], 200);
    }

    /**
     * Update employer profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|string|max:255',
            'contact' => 'sometimes|string|max:20',
            'address' => 'sometimes|array',
            'industry_type' => 'sometimes|exists:industries,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = $request->user();
        $employer->update($request->only(['company_name', 'contact', 'address', 'industry_type']));

        return response()->json([
            'message' => 'Profile updated.',
        ], 200);
    }

    /**
     * Create a new job post
     */
    public function createJob(Request $request)
    {
        Log::info('createJob called', ['data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'category_id' => 'nullable|exists:job_categories,id',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = $request->user()->load('plan');
        Log::info('Employer ID', ['employer_id' => $employer->id]);

        // Check if employer has an active plan
        if (!$employer->plan) {
            Log::error('Employer has no plan assigned');
            return response()->json([
                'message' => 'No plan assigned to your account. Please contact support.'
            ], 403);
        }

        // Check job posting limit
        $jobsCanPost = $employer->plan->jobs_can_post;
        if ($jobsCanPost !== -1) {
            $currentJobCount = Job::where('employer_id', $employer->id)->count();
            Log::info('Job posting check', [
                'current_jobs' => $currentJobCount,
                'limit' => $jobsCanPost
            ]);

            if ($currentJobCount >= $jobsCanPost) {
                Log::error('Job posting limit reached');
                return response()->json([
                    'message' => 'You have reached your job posting limit. Please upgrade your plan to post more jobs.'
                ], 403);
            }
        }

        try {
            $job = Job::create([
                'employer_id' => $employer->id,
                'title' => $request->title,
                'description' => $request->description,
                'salary' => $request->salary,
                'location_id' => $request->location_id,
                'category_id' => $request->category_id,
                'is_featured' => false,
            ]);

            Log::info('Job created successfully', ['job_id' => $job->id]);

            return response()->json([
                'job_id' => $job->id,
                'message' => 'Job created.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Job creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to create job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all jobs posted by the employer
     */
    public function getAllJobs(Request $request)
    {
        $employer = $request->user();

        $jobs = Job::where('employer_id', $employer->id)
            ->with(['location', 'category'])
            ->withCount('applications')
            ->orderBy('created_at', 'desc')
            ->get();

        $jobsData = $jobs->map(function ($job) {
            // Count new applications (applications that came after employer last viewed this job)
            $newApplicationsCount = 0;
            if ($job->last_viewed_at) {
                // Count applications received after last view
                $newApplicationsCount = \App\Models\JobApplication::where('job_id', $job->id)
                    ->where('applied_at', '>', $job->last_viewed_at)
                    ->count();
            } else {
                // If never viewed, all applications are new
                $newApplicationsCount = $job->applications_count;
            }

            return [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'salary' => $job->salary,
                'location' => $job->location,
                'category' => $job->category,
                'is_featured' => $job->is_featured,
                'applications_count' => $job->applications_count,
                'new_applications_count' => $newApplicationsCount,
                'created_at' => $job->created_at,
                'updated_at' => $job->updated_at,
            ];
        });

        return response()->json([
            'jobs' => $jobsData,
        ], 200);
    }

    /**
     * Get details of a single job
     */
    public function getJob(Request $request, $jobId)
    {
        $employer = $request->user();

        $job = Job::where('id', $jobId)
            ->where('employer_id', $employer->id)
            ->with(['location', 'category'])
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json(['job' => $job], 200);
    }

    /**
     * Update a job post
     */
    public function updateJob(Request $request, $jobId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'salary' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'category_id' => 'nullable|exists:job_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = $request->user();

        $job = Job::where('id', $jobId)
            ->where('employer_id', $employer->id)
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $job->update($request->only(['title', 'description', 'salary', 'location_id', 'category_id']));

        return response()->json([
            'message' => 'Job updated.',
        ], 200);
    }

    /**
     * Delete a job post
     */
    public function deleteJob(Request $request, $jobId)
    {
        $employer = $request->user();

        $job = Job::where('id', $jobId)
            ->where('employer_id', $employer->id)
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $job->delete();

        return response()->json([
            'message' => 'Job deleted.',
        ], 200);
    }

    /**
     * Get all applications across all jobs for the employer
     */
    public function getAllApplications(Request $request)
    {
        $employer = $request->user();

        // Get all applications for all jobs posted by this employer
        $applications = JobApplication::whereHas('job', function ($query) use ($employer) {
            $query->where('employer_id', $employer->id);
        })
        ->with(['employee', 'employee.plan', 'employee.educations.degree', 'employee.educations.university', 'employee.educations.fieldOfStudy', 'employee.educations.educationLevel', 'employee.skills', 'job'])
        ->latest()
        ->get();

        // Get all application IDs that have been viewed by this employer
        $viewedApplicationIds = \App\Models\ApplicationContactView::where('employer_id', $employer->id)
            ->whereIn('application_id', $applications->pluck('id'))
            ->pluck('application_id')
            ->toArray();

        $formattedApplications = $applications->map(function ($app) use ($viewedApplicationIds) {
            // Check if this application's contact details have been viewed
            $contactDetailsViewed = in_array($app->id, $viewedApplicationIds);

            // Check if employee has plan feature that allows employers to view contact for free
            $employeeAllowsFreeContactView = $app->employee->plan && $app->employee->plan->employer_can_view_contact_free;

            // Show contact details if either:
            // 1. Already viewed by this employer, OR
            // 2. Employee's plan allows free contact viewing
            $showContactDetails = $contactDetailsViewed || $employeeAllowsFreeContactView;

            // Convert educations from normalized table to array format
            $educationDetails = $app->employee->educations->map(function($edu) {
                return [
                    'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                    'degree' => $edu->degree ? $edu->degree->name : '',
                    'university' => $edu->university ? $edu->university->name : '',
                    'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                    'year_start' => $edu->year_start,
                    'year_end' => $edu->year_end,
                ];
            })->toArray();

            // Convert skills from relationship to array
            $skillsDetails = $app->employee->skills->pluck('name')->toArray();

            // Build employee data
            $employeeData = [
                'id' => $app->employee->id,
                'name' => $app->employee->name,
                'gender' => $app->employee->gender,
                'dob' => $app->employee->dob,
                'education_details' => $educationDetails,
                'experience_details' => $app->employee->experience_details,
                'skills_details' => $skillsDetails,
                'profile_photo_url' => $app->employee->profile_photo_status === 'approved' ? $app->employee->profile_photo_url : null,
                'contact_details_hidden' => !$showContactDetails,
            ];

            // If contact details should be shown, include them
            if ($showContactDetails) {
                $employeeData['email'] = $app->employee->email;
                $employeeData['mobile'] = $app->employee->mobile;
                $employeeData['address'] = $app->employee->address;
            }

            return [
                'id' => $app->id,
                'employee' => $employeeData,
                'job' => [
                    'id' => $app->job->id,
                    'title' => $app->job->title,
                ],
                'applied_at' => $app->applied_at,
                'status' => $app->application_status,
                'interview_date' => $app->interview_date,
                'interview_time' => $app->interview_time,
                'interview_location' => $app->interview_location,
                'contact_details_viewed' => $contactDetailsViewed,
                'employee_allows_free_contact_view' => $employeeAllowsFreeContactView,
            ];
        });

        return response()->json([
            'applications' => $formattedApplications,
        ], 200);
    }

    /**
     * View applications for a job
     */
    public function getJobApplications(Request $request, $jobId)
    {
        $employer = $request->user();

        $job = Job::where('id', $jobId)
            ->where('employer_id', $employer->id)
            ->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Update last_viewed_at timestamp to mark applications as "seen"
        $job->update(['last_viewed_at' => now()]);

        $applications = JobApplication::where('job_id', $jobId)
            ->with(['employee', 'employee.plan', 'employee.educations.degree', 'employee.educations.university', 'employee.educations.fieldOfStudy', 'employee.educations.educationLevel', 'employee.skills'])
            ->latest()
            ->get();

        // Get all application IDs that have been viewed by this employer
        $viewedApplicationIds = \App\Models\ApplicationContactView::where('employer_id', $employer->id)
            ->whereIn('application_id', $applications->pluck('id'))
            ->pluck('application_id')
            ->toArray();

        $formattedApplications = $applications->map(function ($app) use ($viewedApplicationIds) {
            // Check if this application's contact details have been viewed
            $contactDetailsViewed = in_array($app->id, $viewedApplicationIds);

            // Check if employee has plan feature that allows employers to view contact for free
            $employeeAllowsFreeContactView = $app->employee->plan && $app->employee->plan->employer_can_view_contact_free;

            // Show contact details if either:
            // 1. Already viewed by this employer, OR
            // 2. Employee's plan allows free contact viewing
            $showContactDetails = $contactDetailsViewed || $employeeAllowsFreeContactView;

            // Convert educations from normalized table to array format
            $educationDetails = $app->employee->educations->map(function($edu) {
                return [
                    'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                    'degree' => $edu->degree ? $edu->degree->name : '',
                    'university' => $edu->university ? $edu->university->name : '',
                    'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                    'year_start' => $edu->year_start,
                    'year_end' => $edu->year_end,
                ];
            })->toArray();

            // Convert skills from relationship to array
            $skillsDetails = $app->employee->skills->pluck('name')->toArray();

            // Build employee data
            $employeeData = [
                'id' => $app->employee->id,
                'name' => $app->employee->name,
                'gender' => $app->employee->gender,
                'dob' => $app->employee->dob,
                'education_details' => $educationDetails,
                'experience_details' => $app->employee->experience_details,
                'skills_details' => $skillsDetails,
                'profile_photo_url' => $app->employee->profile_photo_status === 'approved' ? $app->employee->profile_photo_url : null,
                'contact_details_hidden' => !$showContactDetails,
            ];

            // If contact details should be shown, include them
            if ($showContactDetails) {
                $employeeData['email'] = $app->employee->email;
                $employeeData['mobile'] = $app->employee->mobile;
                $employeeData['address'] = $app->employee->address;
            }

            return [
                'id' => $app->id,
                'employee' => $employeeData,
                'applied_at' => $app->applied_at,
                'status' => $app->application_status,
                'interview_date' => $app->interview_date,
                'interview_time' => $app->interview_time,
                'interview_location' => $app->interview_location,
                'contact_details_viewed' => $contactDetailsViewed,
                'employee_allows_free_contact_view' => $employeeAllowsFreeContactView,
            ];
        });

        return response()->json([
            'applications' => $formattedApplications,
        ], 200);
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus(Request $request, $appId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:applied,shortlisted,interview_scheduled,selected,rejected',
            'interview_date' => 'nullable|date|required_if:status,interview_scheduled',
            'interview_time' => 'nullable|required_if:status,interview_scheduled',
            'interview_location' => 'nullable|string|required_if:status,interview_scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $application = JobApplication::with('job')->find($appId);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Verify the job belongs to this employer
        if ($application->job->employer_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updateData = [
            'application_status' => $request->status,
        ];

        // Add interview details if status is interview_scheduled
        if ($request->status === 'interview_scheduled') {
            $updateData['interview_date'] = $request->interview_date;
            $updateData['interview_time'] = $request->interview_time;
            $updateData['interview_location'] = $request->interview_location;
        }

        $application->update($updateData);

        // TODO: Trigger WhatsApp notification to employee

        return response()->json([
            'message' => 'Status updated.',
            'whatsapp_sent' => true,
        ], 200);
    }

    /**
     * Get current plan details for employer
     */
    public function getCurrentPlan(Request $request)
    {
        $employer = $request->user()->load(['plan.features', 'currentSubscription']);

        if (!$employer->plan) {
            return response()->json([
                'message' => 'No active plan found',
            ], 404);
        }

        $subscription = $employer->currentSubscription;

        // Initialize contact_views_remaining if it's null in the subscription
        $contactViewsRemaining = null;
        if ($subscription) {
            if ($subscription->contact_views_remaining === null) {
                // Initialize with the plan's limit
                $subscription->contact_views_remaining = $employer->plan->employee_contact_details_can_view;
                $subscription->save();
            }
            $contactViewsRemaining = $subscription->contact_views_remaining;
        } else {
            // No subscription, use the plan's limit as the remaining value
            $contactViewsRemaining = $employer->plan->employee_contact_details_can_view;
        }

        // Calculate jobs remaining
        $jobsCanPost = $employer->plan->jobs_can_post;
        $jobsRemaining = null;
        if ($jobsCanPost !== -1) {
            $currentJobCount = Job::where('employer_id', $employer->id)->count();
            $jobsRemaining = max(0, $jobsCanPost - $currentJobCount);
        } else {
            $jobsRemaining = -1; // Unlimited
        }

        $planData = [
            'id' => $employer->plan->id,
            'name' => $employer->plan->name,
            'description' => $employer->plan->description,
            'price' => $employer->plan->price,
            'features' => $employer->plan->features,
            'is_default' => $employer->plan->is_default,
            'started_at' => $employer->plan_started_at,
            'expires_at' => $employer->plan_expires_at,
            'is_active' => $employer->plan_is_active,
            'is_expired' => $employer->isPlanExpired(),
            'days_remaining' => $employer->plan_expires_at ? Carbon::now()->diffInDays($employer->plan_expires_at, false) : null,
            // Plan limits
            'jobs_can_post' => $employer->plan->jobs_can_post,
            'employee_contact_details_can_view' => $employer->plan->employee_contact_details_can_view,
            // Subscription remaining limits
            'contact_views_remaining' => $contactViewsRemaining,
            'jobs_remaining' => $jobsRemaining,
        ];

        return response()->json([
            'plan' => $planData,
        ], 200);
    }

    /**
     * Get available plans for upgrade (excluding default plans)
     */
    public function getAvailablePlans(Request $request)
    {
        $employer = $request->user();

        // Get all employer plans that are not default
        $plans = Plan::with('features')
                     ->where('type', 'employer')
                     ->where('is_default', false)
                     ->orderBy('price', 'asc')
                     ->get();

        $plansData = $plans->map(function ($plan) use ($employer) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'validity_days' => $plan->validity_days,
                'features' => $plan->features,
                'is_current' => $employer->plan_id === $plan->id,
            ];
        });

        return response()->json([
            'plans' => $plansData,
        ], 200);
    }

    /**
     * Upgrade to a new plan
     */
    public function upgradePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'payment_id' => 'nullable|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = $request->user();
        $newPlan = Plan::find($request->plan_id);

        // Check if plan is for employers
        if ($newPlan->type !== 'employer') {
            return response()->json([
                'message' => 'Invalid plan type',
            ], 400);
        }

        // Check if trying to downgrade to default plan
        if ($newPlan->is_default) {
            return response()->json([
                'message' => 'Cannot upgrade to default plan',
            ], 400);
        }

        // Check if already on this plan
        if ($employer->plan_id === $newPlan->id && $employer->hasActivePlan()) {
            return response()->json([
                'message' => 'Already subscribed to this plan',
            ], 400);
        }

        // Calculate new plan dates
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($newPlan->validity_days);

        // Mark current subscription as expired if exists
        if ($employer->plan_id) {
            EmployerPlanSubscription::where('employer_id', $employer->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
        }

        // Update employer plan details
        $employer->update([
            'plan_id' => $newPlan->id,
            'plan_started_at' => $planStartedAt,
            'plan_expires_at' => $planExpiresAt,
            'plan_is_active' => true,
        ]);

        // Create new subscription record
        $subscription = EmployerPlanSubscription::create([
            'employer_id' => $employer->id,
            'plan_id' => $newPlan->id,
            'payment_id' => $request->payment_id,
            'started_at' => $planStartedAt,
            'expires_at' => $planExpiresAt,
            'status' => 'active',
            'is_default' => false,
            'contact_views_remaining' => $newPlan->employee_contact_details_can_view,
        ]);

        return response()->json([
            'message' => 'Plan upgraded successfully',
            'plan' => [
                'name' => $newPlan->name,
                'started_at' => $planStartedAt->toDateTimeString(),
                'expires_at' => $planExpiresAt->toDateTimeString(),
            ],
            'subscription_id' => $subscription->id,
        ], 200);
    }

    /**
     * Get plan subscription history
     */
    public function getPlanHistory(Request $request)
    {
        $employer = $request->user();

        $subscriptions = EmployerPlanSubscription::where('employer_id', $employer->id)
            ->with('plan.features')
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $subscriptions->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'plan' => [
                    'name' => $subscription->plan->name,
                    'price' => $subscription->plan->price,
                ],
                'started_at' => $subscription->started_at,
                'expires_at' => $subscription->expires_at,
                'status' => $subscription->status,
                'is_default' => $subscription->is_default,
            ];
        });

        return response()->json([
            'history' => $history,
        ], 200);
    }

    /**
     * Download active CV of an employee (for job applications)
     */
    public function downloadEmployeeCV(Request $request, $employeeId)
    {
        Log::info('Download CV Request', [
            'employee_id' => $employeeId,
            'employer_id' => $request->user()->id
        ]);

        $employer = $request->user();

        // Get the job application and verify the employee has applied to this employer's jobs
        $application = JobApplication::whereHas('job', function ($query) use ($employer) {
            $query->where('employer_id', $employer->id);
        })
        ->where('employee_id', $employeeId)
        ->with('employee')
        ->first();

        Log::info('Application check', ['application' => $application ? 'found' : 'not found']);

        if (!$application) {
            return response()->json([
                'message' => 'Unauthorized. This employee has not applied to any of your jobs.',
            ], 403);
        }

        // Get employee from the application relationship
        $employee = $application->employee;

        Log::info('Employee from application', ['employee' => $employee ? $employee->id : 'not found']);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Get the active CV for this employee
        $cv = CV::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        Log::info('CV found', ['cv' => $cv ? $cv->id : 'not found']);

        if (!$cv) {
            return response()->json([
                'message' => 'No active CV found for this employee',
            ], 404);
        }

        if ($cv->type === 'uploaded') {
            // For uploaded CVs, return the file
            $filePath = str_replace('/storage/', '', $cv->file_url);

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json(['message' => 'CV file not found'], 404);
            }

            return response()->download(
                storage_path('app/public/' . $filePath),
                $employee->name . '_CV.pdf'
            );
        } else {
            // For created CVs, generate PDF from profile data
            $employee->load(['plan', 'educations.degree', 'educations.university', 'educations.fieldOfStudy', 'educations.educationLevel', 'skills']);

            // Generate HTML content for CV
            $html = $this->generateCVHtml($employee, $cv->title);

            // Generate PDF from HTML
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');

            // Download the PDF
            return $pdf->download($employee->name . '_CV.pdf');
        }
    }

    /**
     * Generate HTML CV from employee profile data
     */
    private function generateCVHtml($employee, $title)
    {
        // Convert educations from normalized table to array format for template
        $educationDetails = $employee->educations->map(function($edu) {
            return [
                'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                'degree' => $edu->degree ? $edu->degree->name : '',
                'university' => $edu->university ? $edu->university->name : '',
                'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                'year_start' => $edu->year_start,
                'year_end' => $edu->year_end,
            ];
        })->toArray();

        // Convert skills from relationship to array for template
        $skillsDetails = $employee->skills->pluck('name')->toArray();

        // Get profile photo if approved
        $profilePhotoBase64 = null;
        if ($employee->profile_photo_status === 'approved' && $employee->profile_photo_url) {
            $photoPath = str_replace('/storage/', '', $employee->profile_photo_url);
            $fullPath = storage_path('app/public/' . $photoPath);

            if (file_exists($fullPath)) {
                $imageData = file_get_contents($fullPath);
                $imageType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $profilePhotoBase64 = 'data:image/' . $imageType . ';base64,' . base64_encode($imageData);
            }
        }

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header-section { display: flex; align-items: center; gap: 30px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #3498db; }
        .profile-photo { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db; }
        .header-info { flex: 1; }
        h1 { color: #2c3e50; margin: 0; }
        h2 { color: #34495e; border-bottom: 2px solid #95a5a6; padding-bottom: 5px; margin-top: 30px; }
        .contact-info { background: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .section { margin: 20px 0; }
        .item { margin: 15px 0; }
        .item-title { font-weight: bold; color: #2c3e50; }
        .item-subtitle { color: #7f8c8d; font-style: italic; }
        .skills { display: flex; flex-wrap: wrap; gap: 10px; }
        .skill-tag { background: #3498db; color: white; padding: 5px 15px; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="header-section">';

        // Add profile photo if available
        if ($profilePhotoBase64) {
            $html .= '<img src="' . $profilePhotoBase64 . '" alt="Profile Photo" class="profile-photo" />';
        }

        $html .= '<div class="header-info">
            <h1>' . htmlspecialchars($employee->name) . '</h1>
        </div>
    </div>

    <div class="contact-info">
        <p><strong>Email:</strong> ' . htmlspecialchars($employee->email) . '</p>
        ' . ($employee->mobile ? '<p><strong>Mobile:</strong> ' . htmlspecialchars($employee->mobile) . '</p>' : '') . '
        ' . ($employee->gender ? '<p><strong>Gender:</strong> ' . htmlspecialchars($employee->gender) . '</p>' : '') . '
        ' . ($employee->dob ? '<p><strong>Date of Birth:</strong> ' . htmlspecialchars($employee->dob) . '</p>' : '') . '
    </div>';

        // Address
        if ($employee->address) {
            $address = (array) $employee->address;
            $addressParts = array_filter([
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['zip'] ?? '',
                $address['country'] ?? '',
            ]);
            if (!empty($addressParts)) {
                $html .= '<div class="section">
                    <h2>Address</h2>
                    <p>' . htmlspecialchars(implode(', ', $addressParts)) . '</p>
                </div>';
            }
        }

        // Education
        if (!empty($educationDetails)) {
            $html .= '<div class="section">
                <h2>Education</h2>';
            foreach ($educationDetails as $edu) {
                $edu = (array) $edu;
                $html .= '<div class="item">
                    <div class="item-title">' . htmlspecialchars($edu['degree'] ?? '') . '</div>
                    <div class="item-subtitle">' . htmlspecialchars($edu['university'] ?? '') . '</div>
                    <p>' . htmlspecialchars($edu['field'] ?? '') . ' | ' .
                    htmlspecialchars($edu['year_start'] ?? '') . ' - ' .
                    htmlspecialchars($edu['year_end'] ?? '') . '</p>
                </div>';
            }
            $html .= '</div>';
        }

        // Experience
        if ($employee->experience_details && is_array($employee->experience_details)) {
            $html .= '<div class="section">
                <h2>Experience</h2>';
            foreach ($employee->experience_details as $exp) {
                $exp = (array) $exp;
                $html .= '<div class="item">
                    <div class="item-title">' . htmlspecialchars($exp['title'] ?? '') . '</div>
                    <div class="item-subtitle">' . htmlspecialchars($exp['company'] ?? '') . '</div>
                    <p style="color: #7f8c8d;">' .
                    htmlspecialchars($exp['year_start'] ?? '') . ' - ' .
                    htmlspecialchars($exp['year_end'] ?? '') . '</p>
                    <p>' . htmlspecialchars($exp['description'] ?? '') . '</p>
                </div>';
            }
            $html .= '</div>';
        }

        // Skills
        if (!empty($skillsDetails)) {
            $html .= '<div class="section">
                <h2>Skills</h2>
                <div class="skills">';
            foreach ($skillsDetails as $skill) {
                $html .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
            }
            $html .= '</div>
            </div>';
        }

        $html .= '
</body>
</html>';

        return $html;
    }

    /**
     * Search employees with filters
     */
    public function searchEmployees(Request $request)
    {
        $query = \App\Models\Employee::query();

        // Search by name or email
        if ($request->has('q') && $request->q) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->q . '%');
            });
        }

        // Filter by education level
        if ($request->has('education_level_id') && $request->education_level_id) {
            $query->whereHas('educations', function($q) use ($request) {
                $q->where('education_level_id', $request->education_level_id);
            });
        }

        // Filter by degree
        if ($request->has('degree_id') && $request->degree_id) {
            $query->whereHas('educations', function($q) use ($request) {
                $q->where('degree_id', $request->degree_id);
            });
        }

        // Filter by university
        if ($request->has('university_id') && $request->university_id) {
            $query->whereHas('educations', function($q) use ($request) {
                $q->where('university_id', $request->university_id);
            });
        }

        // Filter by field of study
        if ($request->has('field_of_study_id') && $request->field_of_study_id) {
            $query->whereHas('educations', function($q) use ($request) {
                $q->where('field_of_study_id', $request->field_of_study_id);
            });
        }

        // Filter by skills (can be multiple)
        if ($request->has('skill_ids') && is_array($request->skill_ids) && count($request->skill_ids) > 0) {
            $query->whereHas('skills', function($q) use ($request) {
                $q->whereIn('skills.id', $request->skill_ids);
            }, '>=', count($request->skill_ids)); // Employee must have all selected skills
        }

        // Load relationships
        $query->with([
            'educations.degree',
            'educations.university',
            'educations.fieldOfStudy',
            'educations.educationLevel',
            'skills'
        ]);

        // Only show employees with approved profile photos publicly
        $query->where(function($q) {
            $q->where('profile_photo_status', 'approved')
              ->orWhereNull('profile_photo_status');
        });

        // Paginate results
        $perPage = $request->input('limit', 10);
        $employees = $query->paginate($perPage);

        // Format employee data
        $formattedEmployees = collect($employees->items())->map(function($employee) {
            // Convert educations from normalized table to array format
            $educationDetails = $employee->educations->map(function($edu) {
                return [
                    'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                    'degree' => $edu->degree ? $edu->degree->name : '',
                    'university' => $edu->university ? $edu->university->name : '',
                    'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                    'year_start' => $edu->year_start,
                    'year_end' => $edu->year_end,
                ];
            })->toArray();

            // Convert skills from relationship to array
            $skillsDetails = $employee->skills->pluck('name')->toArray();

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'mobile' => $employee->mobile,
                'gender' => $employee->gender,
                'dob' => $employee->dob,
                'description' => $employee->description,
                'address' => $employee->address,
                'education_details' => $educationDetails,
                'experience_details' => $employee->experience_details,
                'skills_details' => $skillsDetails,
                'cv_url' => $employee->cv_url,
                'public_profile_photo_url' => $employee->profile_photo_status === 'approved' ? $employee->profile_photo_url : null,
                'created_at' => $employee->created_at,
            ];
        });

        return response()->json([
            'employees' => [
                'current_page' => $employees->currentPage(),
                'data' => $formattedEmployees,
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'last_page' => $employees->lastPage(),
            ],
        ], 200);
    }

    /**
     * View employee contact details from application (with plan limit check)
     */
    public function viewApplicationContactDetails(Request $request, $appId)
    {
        $employer = $request->user()->load(['plan', 'currentSubscription']);

        // Get the application with employee plan
        $application = JobApplication::with(['employee.plan', 'job'])->find($appId);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Verify the job belongs to this employer
        if ($application->job->employer_id != $employer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if employee's plan allows employers to view contact for free
        $employeeAllowsFreeContactView = $application->employee->plan && $application->employee->plan->employer_can_view_contact_free;

        // If employee allows free contact viewing, return contact details without any checks
        if ($employeeAllowsFreeContactView) {
            return response()->json([
                'contact_details' => [
                    'email' => $application->employee->email,
                    'mobile' => $application->employee->mobile,
                    'address' => $application->employee->address,
                ],
                'can_download_cv' => true,
                'views_remaining' => 'N/A',
                'already_viewed' => false,
                'free_contact_view' => true,
            ], 200);
        }

        // Check if employer has an active plan and subscription
        if (!$employer->plan || !$employer->currentSubscription) {
            return response()->json([
                'message' => 'No active plan found. Please upgrade your plan.',
            ], 403);
        }

        $subscription = $employer->currentSubscription;
        $contactViewsLimit = $subscription->contact_views_remaining;

        // Check if employer has already viewed this application's contact details
        $alreadyViewed = \App\Models\ApplicationContactView::where('application_id', $appId)
            ->where('employer_id', $employer->id)
            ->exists();

        // If already viewed, return contact details without charging
        if ($alreadyViewed) {
            return response()->json([
                'contact_details' => [
                    'email' => $application->employee->email,
                    'mobile' => $application->employee->mobile,
                    'address' => $application->employee->address,
                ],
                'can_download_cv' => true,
                'views_remaining' => $contactViewsLimit === -1 ? 'unlimited' : $contactViewsLimit,
                'already_viewed' => true,
            ], 200);
        }

        // Check if unlimited (-1) or has remaining views
        if ($contactViewsLimit === -1) {
            // Unlimited access - record the view
            \App\Models\ApplicationContactView::create([
                'application_id' => $appId,
                'employer_id' => $employer->id,
                'viewed_at' => now(),
            ]);

            return response()->json([
                'contact_details' => [
                    'email' => $application->employee->email,
                    'mobile' => $application->employee->mobile,
                    'address' => $application->employee->address,
                ],
                'can_download_cv' => true,
                'views_remaining' => 'unlimited',
                'already_viewed' => false,
            ], 200);
        } elseif ($contactViewsLimit > 0) {
            // Has remaining views, decrease by 1 and record the view
            $subscription->decrement('contact_views_remaining');

            \App\Models\ApplicationContactView::create([
                'application_id' => $appId,
                'employer_id' => $employer->id,
                'viewed_at' => now(),
            ]);

            return response()->json([
                'contact_details' => [
                    'email' => $application->employee->email,
                    'mobile' => $application->employee->mobile,
                    'address' => $application->employee->address,
                ],
                'can_download_cv' => true,
                'views_remaining' => $contactViewsLimit - 1,
                'already_viewed' => false,
            ], 200);
        } else {
            // No remaining views
            return response()->json([
                'message' => 'You have reached your contact details view limit. Please upgrade your plan to view more contact details.',
                'views_remaining' => 0,
            ], 403);
        }
    }
}

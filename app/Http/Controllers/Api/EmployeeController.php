<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ShortlistedJob;
use App\Models\CVRequest;
use App\Models\CV;
use App\Models\Plan;
use App\Models\EmployeePlanSubscription;
use App\Models\EmployeeContactView;
use App\Models\Employer;
use App\Models\Skill;
use App\Models\Degree;
use App\Models\University;
use App\Models\FieldOfStudy;
use App\Models\EmployeeEducation;
use App\Models\Company;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeController extends Controller
{
    /**
     * Get employee profile
     */
    public function getProfile(Request $request)
    {
        $employee = $request->user()->load([
            'plan.features',
            'skills',
            'educations.degree',
            'educations.university',
            'educations.fieldOfStudy',
            'educations.educationLevel'
        ]);

        // Convert skills relationship to array of skill names for compatibility
        $employee->skills_details = $employee->skills->pluck('name')->toArray();

        // Convert educations to array format for frontend compatibility
        $employee->education_details = $employee->educations->map(function($edu) {
            return [
                'education_level_id' => $edu->education_level_id,
                'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                'degree' => $edu->degree ? $edu->degree->name : '',
                'university' => $edu->university ? $edu->university->name : '',
                'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                'year_start' => $edu->year_start,
                'year_end' => $edu->year_end,
            ];
        })->toArray();

        return response()->json([
            'user' => $employee,
            'plan' => $employee->plan,
        ], 200);
    }

    /**
     * Update employee profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'field' => 'required|string',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        $allowedFields = ['address', 'education_details', 'experience_details', 'skills_details', 'cv_url'];

        if (!in_array($request->field, $allowedFields)) {
            return response()->json(['message' => 'Field not allowed for update'], 400);
        }

        // Handle skills_details separately using relationship
        if ($request->field === 'skills_details') {
            // Value should be an array of skill IDs or skill names (strings)
            $skillsInput = is_array($request->value) ? $request->value : [];
            $skillIds = [];

            foreach ($skillsInput as $item) {
                // Check if item is numeric (existing skill ID)
                if (is_numeric($item)) {
                    $skillIds[] = (int) $item;
                }
                // Check if item is a string (new custom skill or existing skill name)
                elseif (is_string($item) && !empty(trim($item))) {
                    $skillName = trim($item);

                    // Check if skill already exists (case-insensitive)
                    $existingSkill = Skill::whereRaw('LOWER(name) = ?', [strtolower($skillName)])->first();

                    if ($existingSkill) {
                        // Use existing skill ID
                        $skillIds[] = $existingSkill->id;
                    } else {
                        // Create new skill with pending status (needs admin approval)
                        $newSkill = Skill::create([
                            'name' => $skillName,
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]);
                        $skillIds[] = $newSkill->id;
                    }
                }
            }

            // Sync all skill IDs (both existing and newly created)
            $employee->skills()->sync($skillIds);
        }
        // Handle education_details with normalized table structure
        elseif ($request->field === 'education_details') {
            $educationInput = is_array($request->value) ? $request->value : [];

            // Delete existing education records for this employee
            $employee->educations()->delete();

            // Create new education records
            foreach ($educationInput as $edu) {
                if (!is_array($edu)) continue;

                // Get or create degree
                $degree = null;
                if (isset($edu['degree']) && !empty(trim($edu['degree']))) {
                    $degreeName = trim($edu['degree']);
                    $degree = Degree::firstOrCreate(
                        ['name' => $degreeName],
                        [
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]
                    );
                }

                // Get or create university
                $university = null;
                if (isset($edu['university']) && !empty(trim($edu['university']))) {
                    $universityName = trim($edu['university']);
                    $university = University::firstOrCreate(
                        ['name' => $universityName],
                        [
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]
                    );
                }

                // Get or create field of study
                $fieldOfStudy = null;
                if (isset($edu['field']) && !empty(trim($edu['field']))) {
                    $fieldName = trim($edu['field']);
                    $fieldOfStudy = FieldOfStudy::firstOrCreate(
                        ['name' => $fieldName],
                        [
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]
                    );
                }

                // Get education level ID (convert empty string to null)
                $educationLevelId = null;
                if (isset($edu['education_level_id']) && $edu['education_level_id'] !== '' && $edu['education_level_id'] !== null) {
                    $educationLevelId = (int) $edu['education_level_id'];
                }

                // Create education record with foreign keys
                EmployeeEducation::create([
                    'employee_id' => $employee->id,
                    'education_level_id' => $educationLevelId,
                    'degree_id' => $degree ? $degree->id : null,
                    'university_id' => $university ? $university->id : null,
                    'field_of_study_id' => $fieldOfStudy ? $fieldOfStudy->id : null,
                    'year_start' => $edu['year_start'] ?? '',
                    'year_end' => $edu['year_end'] ?? '',
                ]);
            }
        }
        // Handle experience_details to create Company and JobTitle records
        elseif ($request->field === 'experience_details') {
            $experienceInput = is_array($request->value) ? $request->value : [];

            // Process each experience to create Company and JobTitle records if they don't exist
            foreach ($experienceInput as &$exp) {
                if (!is_array($exp)) continue;

                // Get or create company
                if (isset($exp['company']) && !empty(trim($exp['company']))) {
                    $companyName = trim($exp['company']);
                    $company = Company::firstOrCreate(
                        ['name' => $companyName],
                        [
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]
                    );
                }

                // Get or create job title
                if (isset($exp['title']) && !empty(trim($exp['title']))) {
                    $titleName = trim($exp['title']);
                    $jobTitle = JobTitle::firstOrCreate(
                        ['name' => $titleName],
                        [
                            'approval_status' => 'pending',
                            'created_by' => $employee->id,
                            'created_by_type' => 'employee',
                        ]
                    );
                }
            }

            // Store the experience_details as JSON in employee table
            $employee->update([
                'experience_details' => $experienceInput,
            ]);
        } else {
            $employee->update([
                $request->field => $request->value,
            ]);
        }

        return response()->json([
            'message' => 'Profile updated.',
        ], 200);
    }

    /**
     * Search jobs with filters
     */
    public function searchJobs(Request $request)
    {
        $query = Job::query()->with(['employer', 'location', 'category']);

        if ($request->has('q')) {
            $query->where('title', 'like', '%' . $request->q . '%')
                  ->orWhere('description', 'like', '%' . $request->q . '%');
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Support limit parameter for non-paginated results (e.g., latest 10 jobs on homepage)
        if ($request->has('limit')) {
            $limit = (int) $request->limit;
            $jobs = $query->latest()->limit($limit)->get();

            // Add applied and shortlisted status for each job if user is authenticated
            $employee = $request->user();
            if ($employee) {
                $jobIds = $jobs->pluck('id')->toArray();

                $appliedJobIds = JobApplication::where('employee_id', $employee->id)
                    ->whereIn('job_id', $jobIds)
                    ->pluck('job_id')
                    ->toArray();

                $shortlistedJobIds = ShortlistedJob::where('employee_id', $employee->id)
                    ->whereIn('job_id', $jobIds)
                    ->pluck('job_id')
                    ->toArray();

                $jobs->transform(function ($job) use ($appliedJobIds, $shortlistedJobIds) {
                    $job->is_applied = in_array($job->id, $appliedJobIds);
                    $job->is_shortlisted = in_array($job->id, $shortlistedJobIds);
                    return $job;
                });
            } else {
                $jobs->transform(function ($job) {
                    $job->is_applied = false;
                    $job->is_shortlisted = false;
                    return $job;
                });
            }

            return response()->json(['jobs' => $jobs], 200);
        }

        $jobs = $query->latest()->paginate(20);

        // Add applied and shortlisted status for each job if user is authenticated
        $employee = $request->user();
        if ($employee) {
            // Optimize: Load all applied and shortlisted job IDs at once
            $jobIds = $jobs->pluck('id')->toArray();

            $appliedJobIds = JobApplication::where('employee_id', $employee->id)
                ->whereIn('job_id', $jobIds)
                ->pluck('job_id')
                ->toArray();

            $shortlistedJobIds = ShortlistedJob::where('employee_id', $employee->id)
                ->whereIn('job_id', $jobIds)
                ->pluck('job_id')
                ->toArray();

            $jobs->getCollection()->transform(function ($job) use ($appliedJobIds, $shortlistedJobIds) {
                $job->is_applied = in_array($job->id, $appliedJobIds);
                $job->is_shortlisted = in_array($job->id, $shortlistedJobIds);
                return $job;
            });
        } else {
            // For non-authenticated users, set default values
            $jobs->getCollection()->transform(function ($job) {
                $job->is_applied = false;
                $job->is_shortlisted = false;
                return $job;
            });
        }

        return response()->json(['jobs' => $jobs], 200);
    }

    /**
     * Apply for a job
     */
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
            return response()->json(['message' => 'Already applied to this job'], 400);
        }

        // Get employee's active subscription with plan
        $activeSubscription = EmployeePlanSubscription::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$activeSubscription) {
            return response()->json([
                'message' => 'No active subscription found. Please subscribe to a plan to apply for jobs.',
            ], 403);
        }

        // Check if plan has unlimited job applications
        $hasUnlimitedApplications = $activeSubscription->plan->jobs_can_apply === -1;

        if (!$hasUnlimitedApplications) {
            // Check if employee has remaining job applications
            if ($activeSubscription->jobs_remaining === null || $activeSubscription->jobs_remaining <= 0) {
                return response()->json([
                    'message' => 'You have reached your job application limit for this plan. Please upgrade your plan to apply for more jobs.',
                    'jobs_remaining' => 0,
                ], 403);
            }
        }

        $application = JobApplication::create([
            'job_id' => $jobId,
            'employee_id' => $employee->id,
            'application_status' => 'applied',
            'applied_at' => now(),
        ]);

        // Decrement jobs_remaining if not unlimited
        if (!$hasUnlimitedApplications) {
            $activeSubscription->decrement('jobs_remaining');
            $activeSubscription->refresh();
        }

        // TODO: Trigger WhatsApp notification to employer

        return response()->json([
            'message' => 'Application submitted.',
            'jobs_remaining' => $hasUnlimitedApplications ? -1 : $activeSubscription->jobs_remaining,
        ], 201);
    }

    /**
     * View employer contact details for a job
     */
    public function viewEmployerContact(Request $request, $jobId)
    {
        $employee = $request->user();
        $job = Job::with('employer.industry')->find($jobId);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        if (!$job->employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $employerId = $job->employer_id;

        // Check if already viewed this employer's contact
        $alreadyViewed = EmployeeContactView::where('employee_id', $employee->id)
            ->where('employer_id', $employerId)
            ->first();

        if ($alreadyViewed) {
            // Already viewed, return contact details without charging
            return response()->json([
                'message' => 'Contact details retrieved (previously viewed)',
                'contact' => [
                    'company_name' => $job->employer->company_name,
                    'email' => $job->employer->email,
                    'contact' => $job->employer->contact,
                    'address' => $job->employer->address,
                    'industry' => $job->employer->industry ? $job->employer->industry->name : null,
                ],
                'already_viewed' => true,
            ], 200);
        }

        // Get employee's active subscription with plan
        $activeSubscription = EmployeePlanSubscription::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$activeSubscription) {
            return response()->json([
                'message' => 'No active subscription found. Please subscribe to a plan to view contact details.',
            ], 403);
        }

        // Check if plan has unlimited contact views
        $hasUnlimitedViews = $activeSubscription->plan->contact_details_can_view === -1;

        if (!$hasUnlimitedViews) {
            // Check if employee has remaining contact views
            if ($activeSubscription->contact_views_remaining === null || $activeSubscription->contact_views_remaining <= 0) {
                return response()->json([
                    'message' => 'You have reached your contact view limit for this plan. Please upgrade your plan to view more contacts.',
                    'contact_views_remaining' => 0,
                ], 403);
            }
        }

        // Record the contact view
        EmployeeContactView::create([
            'employee_id' => $employee->id,
            'employer_id' => $employerId,
            'job_id' => $jobId,
        ]);

        // Decrement contact_views_remaining if not unlimited
        if (!$hasUnlimitedViews) {
            $activeSubscription->decrement('contact_views_remaining');
            $activeSubscription->refresh();
        }

        return response()->json([
            'message' => 'Contact details retrieved successfully',
            'contact' => [
                'company_name' => $job->employer->company_name,
                'email' => $job->employer->email,
                'contact' => $job->employer->contact,
                'address' => $job->employer->address,
                'industry' => $job->employer->industry ? $job->employer->industry->name : null,
            ],
            'contact_views_remaining' => $hasUnlimitedViews ? -1 : $activeSubscription->contact_views_remaining,
            'already_viewed' => false,
        ], 200);
    }

    /**
     * Get jobs applied for
     */
    public function getAppliedJobs(Request $request)
    {
        $employee = $request->user();

        $applications = JobApplication::where('employee_id', $employee->id)
            ->with(['job.employer', 'job.location', 'job.category'])
            ->latest()
            ->get();

        $jobs = $applications->map(function ($application) {
            $job = $application->job;
            $job->status = $application->application_status;
            $job->applied_at = $application->applied_at;
            $job->interview_date = $application->interview_date;
            $job->interview_time = $application->interview_time;
            $job->interview_location = $application->interview_location;
            return $job;
        });

        // Add applied and shortlisted status for each job
        $jobIds = $jobs->pluck('id')->toArray();

        $shortlistedJobIds = ShortlistedJob::where('employee_id', $employee->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->toArray();

        $jobs = $jobs->map(function ($job) use ($shortlistedJobIds) {
            $job->is_applied = true; // All jobs in this list are applied
            $job->is_shortlisted = in_array($job->id, $shortlistedJobIds);
            return $job;
        });

        return response()->json(['jobs' => $jobs], 200);
    }

    /**
     * Shortlist a job
     */
    public function shortlistJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:jobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Check if already shortlisted
        $existing = ShortlistedJob::where('job_id', $request->job_id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Job already shortlisted'], 400);
        }

        ShortlistedJob::create([
            'job_id' => $request->job_id,
            'employee_id' => $employee->id,
        ]);

        return response()->json([
            'message' => 'Job shortlisted.',
        ], 201);
    }

    /**
     * Get shortlisted jobs
     */
    public function getShortlistedJobs(Request $request)
    {
        $employee = $request->user();

        $shortlisted = ShortlistedJob::where('employee_id', $employee->id)
            ->with(['job.employer', 'job.location', 'job.category'])
            ->latest()
            ->get();

        $jobs = $shortlisted->map(function ($item) {
            $job = $item->job;
            $job->shortlist_id = $item->id; // Add shortlist ID for deletion
            return $job;
        });

        // Add applied and shortlisted status for each job
        $jobIds = $jobs->pluck('id')->toArray();

        $appliedJobIds = JobApplication::where('employee_id', $employee->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->toArray();

        $jobs = $jobs->map(function ($job) use ($appliedJobIds) {
            $job->is_applied = in_array($job->id, $appliedJobIds);
            $job->is_shortlisted = true; // All jobs in this list are shortlisted
            return $job;
        });

        return response()->json(['jobs' => $jobs], 200);
    }

    /**
     * Remove job from shortlist
     */
    public function removeShortlist(Request $request, $id)
    {
        $employee = $request->user();

        $shortlisted = ShortlistedJob::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$shortlisted) {
            return response()->json(['message' => 'Shortlisted job not found'], 404);
        }

        $shortlisted->delete();

        return response()->json([
            'message' => 'Job removed from shortlist.',
        ], 200);
    }

    /**
     * Get jobs with viewed contact details
     */
    public function getContactViewedJobs(Request $request)
    {
        $employee = $request->user();

        $contactViews = EmployeeContactView::where('employee_id', $employee->id)
            ->with(['job.employer', 'job.location', 'job.category'])
            ->latest()
            ->get();

        $jobs = $contactViews->map(function ($contactView) {
            $job = $contactView->job;
            if ($job) {
                $job->contact_viewed_at = $contactView->created_at;
            }
            return $job;
        })->filter(); // Remove null jobs

        // Add applied and shortlisted status for each job
        $jobIds = $jobs->pluck('id')->toArray();

        $appliedJobIds = JobApplication::where('employee_id', $employee->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->toArray();

        $shortlistedJobIds = ShortlistedJob::where('employee_id', $employee->id)
            ->whereIn('job_id', $jobIds)
            ->pluck('job_id')
            ->toArray();

        $jobs = $jobs->map(function ($job) use ($appliedJobIds, $shortlistedJobIds) {
            $job->is_applied = in_array($job->id, $appliedJobIds);
            $job->is_shortlisted = in_array($job->id, $shortlistedJobIds);
            return $job;
        });

        return response()->json(['jobs' => $jobs->values()], 200);
    }

    /**
     * Generate non-professional CV from profile data
     */
    public function generateCV(Request $request)
    {
        $employee = $request->user()->load('plan', 'educations.degree', 'educations.university', 'educations.fieldOfStudy', 'educations.educationLevel', 'skills');

        // Convert educations from normalized table to array format
        $educationData = $employee->educations->map(function($edu) {
            return [
                'education_level' => $edu->educationLevel ? $edu->educationLevel->name : '',
                'degree' => $edu->degree ? $edu->degree->name : '',
                'university' => $edu->university ? $edu->university->name : '',
                'field' => $edu->fieldOfStudy ? $edu->fieldOfStudy->name : '',
                'year_start' => $edu->year_start,
                'year_end' => $edu->year_end,
            ];
        })->toArray();

        // Generate CV data from profile
        $cvData = [
            'name' => $employee->name,
            'email' => $employee->email,
            'mobile' => $employee->mobile,
            'gender' => $employee->gender,
            'dob' => $employee->dob,
            'address' => $employee->address,
            'education' => $educationData,
            'experience' => $employee->experience_details,
            'skills' => $employee->skills->pluck('name')->toArray(),
            'generated_at' => now(),
        ];

        return response()->json([
            'message' => 'CV generated successfully',
            'cv_data' => $cvData,
        ], 200);
    }

    /**
     * Upload professional CV
     */
    public function uploadCV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Store CV file
        $file = $request->file('cv_file');
        $filename = 'cv_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cvs', $filename, 'public');

        // Update employee CV URL
        $employee->update([
            'cv_url' => '/storage/' . $path,
        ]);

        return response()->json([
            'message' => 'CV uploaded successfully',
            'cv_url' => $employee->cv_url,
        ], 200);
    }

    /**
     * Request professional CV creation service
     */
    public function requestProfessionalCV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'preferred_template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Create CV request
        $cvRequest = CVRequest::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'notes' => $request->notes,
            'preferred_template' => $request->preferred_template,
            'status' => 'pending',
        ]);

        // TODO: Integrate with professional CV service API
        // TODO: Send notification to admin/CV service provider

        return response()->json([
            'message' => 'Professional CV request submitted',
            'request_id' => $cvRequest->id,
            'status' => $cvRequest->status,
            'estimated_delivery' => now()->addDays(3)->format('Y-m-d'),
        ], 201);
    }

    /**
     * Get CV request status
     */
    public function getCVRequestStatus(Request $request, $requestId)
    {
        $employee = $request->user();

        $cvRequest = CVRequest::where('id', $requestId)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$cvRequest) {
            return response()->json(['message' => 'CV request not found'], 404);
        }

        return response()->json([
            'request' => $cvRequest,
        ], 200);
    }

    /**
     * Get all CV requests for employee
     */
    public function getMyCVRequests(Request $request)
    {
        $employee = $request->user();

        $requests = CVRequest::where('employee_id', $employee->id)
            ->latest()
            ->get();

        return response()->json([
            'requests' => $requests,
        ], 200);
    }

    /**
     * Get all CVs for the authenticated employee
     */
    public function getAllCVs(Request $request)
    {
        $employee = $request->user();

        $cvs = CV::where('employee_id', $employee->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'cvs' => $cvs,
        ], 200);
    }

    /**
     * Upload a new CV with title
     */
    public function uploadCVWithTitle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cv_file' => 'required|file|mimes:pdf|max:5120', // 5MB max, PDF only
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Store CV file
        $file = $request->file('cv_file');
        $filename = 'cv_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cvs', $filename, 'public');

        // Create CV record
        $cv = CV::create([
            'employee_id' => $employee->id,
            'title' => $request->title,
            'type' => 'uploaded',
            'file_url' => '/storage/' . $path,
            'is_active' => false,
        ]);

        // If this is the first CV, set it as active automatically
        $cvCount = CV::where('employee_id', $employee->id)->count();
        if ($cvCount === 1) {
            $cv->setAsActive();
        }

        return response()->json([
            'message' => 'CV uploaded successfully',
            'cv' => $cv,
        ], 201);
    }

    /**
     * Create a new CV using profile data
     */
    public function createCVWithProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Create CV record (file will be generated when accessed)
        $cv = CV::create([
            'employee_id' => $employee->id,
            'title' => $request->title,
            'type' => 'created',
            'file_url' => null, // Will be generated on-demand
            'is_active' => false,
        ]);

        // If this is the first CV, set it as active automatically
        $cvCount = CV::where('employee_id', $employee->id)->count();
        if ($cvCount === 1) {
            $cv->setAsActive();
        }

        return response()->json([
            'message' => 'CV created successfully',
            'cv' => $cv,
        ], 201);
    }

    /**
     * Set a CV as active
     */
    public function setActiveCVById(Request $request, $cvId)
    {
        $employee = $request->user();

        $cv = CV::where('id', $cvId)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$cv) {
            return response()->json(['message' => 'CV not found'], 404);
        }

        $cv->setAsActive();

        return response()->json([
            'message' => 'Active CV updated successfully',
            'cv' => $cv->fresh(),
        ], 200);
    }

    /**
     * Delete a CV
     */
    public function deleteCVById(Request $request, $cvId)
    {
        $employee = $request->user();

        $cv = CV::where('id', $cvId)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$cv) {
            return response()->json(['message' => 'CV not found'], 404);
        }

        // Don't allow deletion if it's the only CV
        $cvCount = CV::where('employee_id', $employee->id)->count();
        if ($cvCount === 1) {
            return response()->json([
                'message' => 'Cannot delete the only CV. Upload or create another CV first.',
            ], 400);
        }

        // If deleting the active CV, set another CV as active
        if ($cv->is_active) {
            $nextCV = CV::where('employee_id', $employee->id)
                ->where('id', '!=', $cv->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($nextCV) {
                $nextCV->setAsActive();
            }
        }

        // Delete the file if it's an uploaded CV
        if ($cv->type === 'uploaded' && $cv->file_url) {
            $filePath = str_replace('/storage/', '', $cv->file_url);
            Storage::disk('public')->delete($filePath);
        }

        $cv->delete();

        return response()->json([
            'message' => 'CV deleted successfully',
        ], 200);
    }

    /**
     * Download a CV
     */
    public function downloadCVById(Request $request, $cvId)
    {
        $employee = $request->user();

        $cv = CV::where('id', $cvId)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$cv) {
            return response()->json(['message' => 'CV not found'], 404);
        }

        if ($cv->type === 'uploaded') {
            // For uploaded CVs, return the file
            $filePath = str_replace('/storage/', '', $cv->file_url);

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json(['message' => 'CV file not found'], 404);
            }

            return response()->download(
                storage_path('app/public/' . $filePath),
                $cv->title . '.pdf'
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
            return $pdf->download($cv->title . '.pdf');
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
     * Get current plan details for employee
     */
    public function getCurrentPlan(Request $request)
    {
        $employee = $request->user()->load(['plan.features', 'currentSubscription']);

        if (!$employee->plan) {
            return response()->json([
                'message' => 'No active plan found',
            ], 404);
        }

        // Get jobs remaining and contact views remaining from active subscription
        $jobsRemaining = null;
        $contactViewsRemaining = null;
        if ($employee->currentSubscription) {
            $jobsRemaining = $employee->currentSubscription->jobs_remaining;
            $contactViewsRemaining = $employee->currentSubscription->contact_views_remaining;
        }

        $planData = [
            'id' => $employee->plan->id,
            'name' => $employee->plan->name,
            'description' => $employee->plan->description,
            'price' => $employee->plan->price,
            'features' => $employee->plan->features,
            'is_default' => $employee->plan->is_default,
            'started_at' => $employee->plan_started_at,
            'expires_at' => $employee->plan_expires_at,
            'is_active' => $employee->plan_is_active,
            'is_expired' => $employee->isPlanExpired(),
            'days_remaining' => $employee->plan_expires_at ? Carbon::now()->diffInDays($employee->plan_expires_at, false) : null,
            'jobs_can_apply' => $employee->plan->jobs_can_apply,
            'jobs_remaining' => $jobsRemaining,
            'contact_details_can_view' => $employee->plan->contact_details_can_view,
            'contact_views_remaining' => $contactViewsRemaining,
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
        $employee = $request->user();

        // Get all employee plans that are not default
        $plans = Plan::with('features')
                     ->where('type', 'employee')
                     ->where('is_default', false)
                     ->orderBy('price', 'asc')
                     ->get();

        $plansData = $plans->map(function ($plan) use ($employee) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'validity_days' => $plan->validity_days,
                'features' => $plan->features,
                'is_current' => $employee->plan_id === $plan->id,
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

        $employee = $request->user();
        $newPlan = Plan::find($request->plan_id);

        // Check if plan is for employees
        if ($newPlan->type !== 'employee') {
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
        if ($employee->plan_id === $newPlan->id && $employee->hasActivePlan()) {
            return response()->json([
                'message' => 'Already subscribed to this plan',
            ], 400);
        }

        // Calculate new plan dates
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($newPlan->validity_days);

        // Mark current subscription as expired if exists
        if ($employee->plan_id) {
            EmployeePlanSubscription::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
        }

        // Update employee plan details
        $employee->update([
            'plan_id' => $newPlan->id,
            'plan_started_at' => $planStartedAt,
            'plan_expires_at' => $planExpiresAt,
            'plan_is_active' => true,
        ]);

        // Create new subscription record
        $subscription = EmployeePlanSubscription::create([
            'employee_id' => $employee->id,
            'plan_id' => $newPlan->id,
            'payment_id' => $request->payment_id,
            'started_at' => $planStartedAt,
            'expires_at' => $planExpiresAt,
            'status' => 'active',
            'is_default' => false,
            'jobs_remaining' => $newPlan->jobs_can_apply, // Initialize with plan's limit (-1 for unlimited)
            'contact_views_remaining' => $newPlan->contact_details_can_view, // Initialize with plan's contact view limit
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
        $employee = $request->user();

        $subscriptions = EmployeePlanSubscription::where('employee_id', $employee->id)
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
     * Upload profile photo (requires admin approval)
     */
    public function uploadProfilePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_photo' => 'required|image|mimes:jpeg,jpg,png|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Delete old photo if exists and status was rejected or pending
        if ($employee->profile_photo_url && in_array($employee->profile_photo_status, ['pending', 'rejected'])) {
            $oldPath = str_replace('/storage/', '', $employee->profile_photo_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Store the new photo
        $file = $request->file('profile_photo');
        $filename = 'profile_photos/' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile_photos', $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension(), 'public');

        // Update employee record
        $employee->update([
            'profile_photo_url' => '/storage/' . $path,
            'profile_photo_status' => 'pending',
            'profile_photo_rejection_reason' => null,
        ]);

        // Refresh the employee to get computed attributes
        $employee->refresh();

        return response()->json([
            'message' => 'Profile photo uploaded successfully. Pending admin approval.',
            'profile_photo_url' => $employee->profile_photo_full_url,
            'profile_photo_status' => 'pending',
        ], 200);
    }

    /**
     * Get profile photo status
     */
    public function getProfilePhotoStatus(Request $request)
    {
        $employee = $request->user();

        return response()->json([
            'profile_photo_url' => $employee->profile_photo_full_url,
            'profile_photo_status' => $employee->profile_photo_status,
            'profile_photo_rejection_reason' => $employee->profile_photo_rejection_reason,
        ], 200);
    }
}

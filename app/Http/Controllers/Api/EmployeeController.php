<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\ShortlistedJob;
use App\Models\CVRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Get employee profile
     */
    public function getProfile(Request $request)
    {
        $employee = $request->user()->load('plan.features');

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

        $employee->update([
            $request->field => $request->value,
        ]);

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

        $jobs = $query->latest()->paginate(20);

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

        $application = JobApplication::create([
            'job_id' => $jobId,
            'employee_id' => $employee->id,
            'application_status' => 'applied',
            'applied_at' => now(),
        ]);

        // TODO: Trigger WhatsApp notification to employer

        return response()->json([
            'message' => 'Application submitted.',
        ], 201);
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
            return [
                'id' => $application->job->id,
                'title' => $application->job->title,
                'status' => $application->application_status,
                'employer' => $application->job->employer,
                'applied_at' => $application->applied_at,
            ];
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
            return $item->job;
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
     * Generate non-professional CV from profile data
     */
    public function generateCV(Request $request)
    {
        $employee = $request->user()->load('plan');

        // Generate CV data from profile
        $cvData = [
            'name' => $employee->name,
            'email' => $employee->email,
            'mobile' => $employee->mobile,
            'gender' => $employee->gender,
            'dob' => $employee->dob,
            'address' => $employee->address,
            'education' => $employee->education_details,
            'experience' => $employee->experience_details,
            'skills' => $employee->skills_details,
            'generated_at' => now(),
        ];

        return response()->json([
            'message' => 'CV generated successfully',
            'cv_data' => $cvData,
            'download_url' => route('employee.cv.download', ['id' => $employee->id]),
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
}

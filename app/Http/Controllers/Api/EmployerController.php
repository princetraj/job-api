<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'salary' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'category_id' => 'nullable|exists:job_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = $request->user();

        $job = Job::create([
            'employer_id' => $employer->id,
            'title' => $request->title,
            'description' => $request->description,
            'salary' => $request->salary,
            'location_id' => $request->location_id,
            'category_id' => $request->category_id,
            'is_featured' => false,
        ]);

        return response()->json([
            'job_id' => $job->id,
            'message' => 'Job created.',
        ], 201);
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

        $applications = JobApplication::where('job_id', $jobId)
            ->with('employee')
            ->latest()
            ->get();

        $formattedApplications = $applications->map(function ($app) {
            return [
                'id' => $app->id,
                'employee' => $app->employee,
                'applied_at' => $app->applied_at,
                'status' => $app->application_status,
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

        $application->update([
            'application_status' => $request->status,
        ]);

        // TODO: Trigger WhatsApp notification to employee

        return response()->json([
            'message' => 'Status updated.',
            'whatsapp_sent' => true,
        ], 200);
    }
}

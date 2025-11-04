<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Admin;
use App\Models\Plan;
use App\Models\EmployeePlanSubscription;
use App\Models\EmployeeEducation;
use App\Models\Degree;
use App\Models\University;
use App\Models\FieldOfStudy;
use App\Models\Company;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Employee Registration - Step 1 (Account Details)
     */
    public function employeeRegisterStep1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:employees,email',
            'mobile' => 'required|unique:employees,mobile',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:M,F,O',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the default plan for employees
        $defaultPlan = Plan::getDefaultPlan('employee');

        if (!$defaultPlan) {
            return response()->json([
                'message' => 'Default plan not found. Please contact support.',
            ], 500);
        }

        // Calculate plan expiry date
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($defaultPlan->validity_days);

        $employee = Employee::create([
            'email' => $request->email,
            'mobile' => $request->mobile,
            'name' => $request->name,
            'password' => $request->password,
            'gender' => $request->gender,
            'plan_id' => $defaultPlan->id,
            'plan_started_at' => $planStartedAt,
            'plan_expires_at' => $planExpiresAt,
            'plan_is_active' => true,
        ]);

        // Create subscription record
        EmployeePlanSubscription::create([
            'employee_id' => $employee->id,
            'plan_id' => $defaultPlan->id,
            'started_at' => $planStartedAt,
            'expires_at' => $planExpiresAt,
            'status' => 'active',
            'is_default' => true,
            'jobs_remaining' => $defaultPlan->jobs_can_apply, // Initialize with plan's limit (-1 for unlimited)
            'contact_views_remaining' => $defaultPlan->contact_details_can_view, // Initialize with plan's contact view limit
        ]);

        $tempToken = $employee->createToken('temp-token')->plainTextToken;

        return response()->json([
            'message' => 'Step 1 complete. Default plan assigned.',
            'tempToken' => $tempToken,
            'plan' => [
                'name' => $defaultPlan->name,
                'expires_at' => $planExpiresAt->toDateTimeString(),
            ],
        ], 200);
    }

    /**
     * Employee Registration - Step 2 (Basic Details)
     */
    public function employeeRegisterStep2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dob' => 'nullable|date',
            'address' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();
        $employee->update([
            'dob' => $request->dob,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Step 2 complete.',
        ], 200);
    }

    /**
     * Employee Registration - Final Step (Education, Experience, Skills)
     */
    public function employeeRegisterFinal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'education' => 'nullable|array',
            'experience' => 'nullable|array',
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();

        // Process experience details and create Company and JobTitle records if they don't exist
        // Expected fields: company, title, description, year_start, year_end, month_start, month_end
        $experienceInput = is_array($request->experience) ? $request->experience : [];
        foreach ($experienceInput as &$exp) {
            if (!is_array($exp)) continue;

            // Validate month fields if provided (1-12)
            if (isset($exp['month_start'])) {
                $exp['month_start'] = max(1, min(12, (int)$exp['month_start']));
            }
            if (isset($exp['month_end'])) {
                $exp['month_end'] = max(1, min(12, (int)$exp['month_end']));
            }

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

        $employee->update([
            'experience_details' => $experienceInput,
        ]);

        // Save education to normalized tables
        if ($request->has('education') && is_array($request->education)) {
            // Delete existing education records for this employee (if any)
            $employee->educations()->delete();

            foreach ($request->education as $edu) {
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

        // Sync skills relationship
        if ($request->has('skills')) {
            $employee->skills()->sync($request->skills);
        }

        // Revoke temp token and create permanent token
        $request->user()->tokens()->delete();
        $token = $employee->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration complete.',
            'token' => $token,
        ], 200);
    }

    /**
     * Employer Registration
     */
    public function employerRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employers,email',
            'contact' => 'required|string',
            'address' => 'nullable|array',
            'industry_type_id' => 'required|exists:industries,id',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the default plan for employers
        $defaultPlan = Plan::getDefaultPlan('employer');

        if (!$defaultPlan) {
            return response()->json([
                'message' => 'Default plan not found. Please contact support.',
            ], 500);
        }

        // Calculate plan expiry date
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($defaultPlan->validity_days);

        $employer = Employer::create([
            'company_name' => $request->company_name,
            'email' => $request->email,
            'contact' => $request->contact,
            'address' => $request->address,
            'industry_type' => $request->industry_type_id,
            'password' => $request->password,
            'plan_id' => $defaultPlan->id,
            'plan_started_at' => $planStartedAt,
            'plan_expires_at' => $planExpiresAt,
            'plan_is_active' => true,
        ]);

        $token = $employer->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration complete. Default plan assigned.',
            'token' => $token,
            'plan' => [
                'name' => $defaultPlan->name,
                'expires_at' => $planExpiresAt->toDateTimeString(),
            ],
        ], 201);
    }

    /**
     * User Login (Employee, Employer, or Admin)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Try to find user in employees table
        $employee = Employee::where(function ($query) use ($request) {
            $query->where('email', $request->identifier)
                ->orWhere('mobile', $request->identifier);
        })->first();

        if ($employee && Hash::check($request->password, $employee->password)) {
            $token = $employee->createToken('auth-token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user_type' => 'employee',
                'user' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'mobile' => $employee->mobile,
                ],
            ], 200);
        }

        // Try to find user in employers table
        $employer = Employer::where('email', $request->identifier)->first();

        if ($employer && Hash::check($request->password, $employer->password)) {
            $token = $employer->createToken('auth-token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user_type' => 'employer',
                'user' => [
                    'id' => $employer->id,
                    'company_name' => $employer->company_name,
                    'email' => $employer->email,
                    'contact' => $employer->contact,
                ],
            ], 200);
        }

        // Try to find user in admins table
        $admin = Admin::where('email', $request->identifier)->first();

        if ($admin && Hash::check($request->password, $admin->password)) {
            $token = $admin->createToken('auth-token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user_type' => 'admin',
                'user' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
}

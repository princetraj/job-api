<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

        $employee = Employee::create([
            'email' => $request->email,
            'mobile' => $request->mobile,
            'name' => $request->name,
            'password' => $request->password,
            'gender' => $request->gender,
        ]);

        $tempToken = $employee->createToken('temp-token')->plainTextToken;

        return response()->json([
            'message' => 'Step 1 complete.',
            'tempToken' => $tempToken,
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = $request->user();
        $employee->update([
            'education_details' => $request->education,
            'experience_details' => $request->experience,
            'skills_details' => $request->skills,
        ]);

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

        $employer = Employer::create([
            'company_name' => $request->company_name,
            'email' => $request->email,
            'contact' => $request->contact,
            'address' => $request->address,
            'industry_type' => $request->industry_type_id,
            'password' => $request->password,
        ]);

        $token = $employer->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration complete.',
            'token' => $token,
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
        $employee = Employee::where('email', $request->identifier)
            ->orWhere('mobile', $request->identifier)
            ->first();

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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Job;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\CommissionTransaction;
use App\Models\CVRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get admin profile
     */
    public function getProfile(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'admin' => $admin,
        ], 200);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request)
    {
        $admin = $request->user();

        $stats = [
            'total_employees' => Employee::count(),
            'total_employers' => Employer::count(),
            'total_jobs' => Job::count(),
            'active_jobs' => Job::where('created_at', '>=', now()->subDays(30))->count(),
            'total_applications' => \App\Models\JobApplication::count(),
            'pending_cv_requests' => CVRequest::where('status', 'pending')->count(),
        ];

        // Role-specific stats
        if ($admin->role === 'super_admin') {
            $stats['total_commissions'] = (string) CommissionTransaction::sum('amount_earned');
            $stats['total_coupons'] = Coupon::count();
        }

        return response()->json($stats, 200);
    }

    /**
     * Get all employees (Employee Manager / Super Admin)
     */
    public function getEmployees(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $employees = Employee::with('plan')->paginate(50);

        return response()->json(['employees' => $employees], 200);
    }

    /**
     * Get single employee
     */
    public function getEmployee(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $employee = Employee::with('plan', 'jobApplications')->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json(['employee' => $employee], 200);
    }

    /**
     * Update employee
     */
    public function updateEmployee(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->update($request->only(['name', 'email', 'mobile', 'plan_id']));

        return response()->json(['message' => 'Employee updated'], 200);
    }

    /**
     * Delete employee
     */
    public function deleteEmployee(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted'], 200);
    }

    /**
     * Get all employers (Employer Manager / Super Admin)
     */
    public function getEmployers(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'employer_manager']);

        $employers = Employer::with('plan', 'industry')->paginate(50);

        return response()->json(['employers' => $employers], 200);
    }

    /**
     * Get single employer
     */
    public function getEmployer(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employer_manager']);

        $employer = Employer::with('plan', 'industry', 'jobs')->find($id);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        return response()->json(['employer' => $employer], 200);
    }

    /**
     * Update employer
     */
    public function updateEmployer(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employer_manager']);

        $employer = Employer::find($id);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $employer->update($request->only(['company_name', 'email', 'contact', 'plan_id']));

        return response()->json(['message' => 'Employer updated'], 200);
    }

    /**
     * Delete employer
     */
    public function deleteEmployer(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employer_manager']);

        $employer = Employer::find($id);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $employer->delete();

        return response()->json(['message' => 'Employer deleted'], 200);
    }

    /**
     * Get all jobs (Super Admin / Employer Manager)
     */
    public function getJobs(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'employer_manager']);

        $jobs = Job::with(['employer', 'location', 'category'])->paginate(50);

        return response()->json(['jobs' => $jobs], 200);
    }

    /**
     * Create coupon (Super Admin / Plan Upgrade Manager)
     */
    public function createCoupon(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'plan_upgrade_manager']);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons,code',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'expiry_date' => 'required|date',
            'staff_id' => 'required|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::create([
            'code' => $request->code,
            'discount_percentage' => $request->discount_percentage,
            'expiry_date' => $request->expiry_date,
            'staff_id' => $request->staff_id,
        ]);

        return response()->json([
            'message' => 'Coupon created',
            'coupon' => $coupon,
        ], 201);
    }

    /**
     * Get all coupons
     */
    public function getCoupons(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'plan_upgrade_manager']);

        $coupons = Coupon::with('staff')->get();

        return response()->json(['coupons' => $coupons], 200);
    }

    /**
     * Add manual commission (Super Admin / Plan Upgrade Manager)
     */
    public function addManualCommission(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'plan_upgrade_manager']);

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:admins,id',
            'amount_earned' => 'required|numeric|min:0',
            'payment_id' => 'nullable|exists:payments,id',
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
            'message' => 'Commission added',
            'commission' => $commission,
        ], 201);
    }

    /**
     * View all commissions (Super Admin)
     */
    public function getAllCommissions(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $commissions = CommissionTransaction::with(['staff', 'payment'])->latest()->get();

        return response()->json(['commissions' => $commissions], 200);
    }

    /**
     * View staff member's commissions
     */
    public function getStaffCommissions(Request $request)
    {
        $admin = $request->user();

        $commissions = CommissionTransaction::where('staff_id', $admin->id)
            ->with('payment')
            ->latest()
            ->get();

        $totalEarned = $commissions->sum('amount_earned');

        return response()->json([
            'commissions' => $commissions,
            'total_earned' => $totalEarned,
        ], 200);
    }

    /**
     * Get all admins (Super Admin only)
     */
    public function getAdmins(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $admins = Admin::latest()->paginate(50);

        return response()->json(['admins' => $admins], 200);
    }

    /**
     * Get single admin (Super Admin only)
     */
    public function getAdmin(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin']);

        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        return response()->json(['admin' => $admin], 200);
    }

    /**
     * Create new admin (Super Admin only)
     */
    public function createAdmin(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,employee_manager,employer_manager,plan_upgrade_manager,catalog_manager',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Will be hashed by model mutator
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Update admin (Super Admin only)
     */
    public function updateAdmin(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin']);

        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:admins,email,' . $id,
            'role' => 'sometimes|in:super_admin,employee_manager,employer_manager,plan_upgrade_manager,catalog_manager',
            'password' => 'sometimes|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only(['name', 'email', 'role']);

        if ($request->has('password')) {
            $updateData['password'] = $request->password; // Will be hashed by model mutator
        }

        $admin->update($updateData);

        return response()->json([
            'message' => 'Admin updated successfully',
            'admin' => $admin,
        ], 200);
    }

    /**
     * Delete admin (Super Admin only)
     */
    public function deleteAdmin(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin']);

        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        // Prevent deleting self
        if ($admin->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 403);
        }

        $admin->delete();

        return response()->json([
            'message' => 'Admin deleted successfully',
        ], 200);
    }

    /**
     * Get all CV requests (Employee Manager / Super Admin)
     */
    public function getCVRequests(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $cvRequests = CVRequest::with('employee')->latest()->paginate(20);

        return response()->json(['cv_requests' => $cvRequests], 200);
    }

    /**
     * Update CV request status (Employee Manager / Super Admin)
     */
    public function updateCVRequestStatus(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'employee_manager']);

        $cvRequest = CVRequest::find($id);

        if (!$cvRequest) {
            return response()->json(['message' => 'CV request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,in_progress,completed,rejected',
            'cv_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = ['status' => $request->status];

        if ($request->status === 'completed') {
            $updateData['completed_at'] = now();
            if ($request->cv_url) {
                $updateData['cv_url'] = $request->cv_url;
            }
        }

        $cvRequest->update($updateData);

        // TODO: Send notification to employee about status update

        return response()->json([
            'message' => 'CV request updated',
            'cv_request' => $cvRequest->fresh(),
        ], 200);
    }

    /**
     * Helper method to authorize roles
     */
    private function authorizeRole(Request $request, array $allowedRoles)
    {
        $admin = $request->user();

        if (!in_array($admin->role, $allowedRoles)) {
            abort(403, 'Unauthorized access');
        }
    }
}

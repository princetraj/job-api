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
use App\Models\Plan;
use App\Models\EmployeePlanSubscription;
use App\Models\EmployerPlanSubscription;
use App\Models\PlanOrder;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = Employee::with('plan.features');

        // Search by name, email, or mobile
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        // Filter by date range (created_at)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by plan_id
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by account_status
        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $employees = $query->paginate($request->input('per_page', 20));

        return response()->json(['employees' => $employees], 200);
    }

    /**
     * Get single employee
     */
    public function getEmployee(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employee = Employee::with('plan.features', 'jobApplications')->find($id);

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
        $this->authorizeRole($request, ['super_admin', 'manager']);

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
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted'], 200);
    }

    /**
     * Approve employee account
     */
    public function approveEmployee(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->update(['account_status' => 'approved']);

        return response()->json(['message' => 'Employee approved successfully'], 200);
    }

    /**
     * Export employees to Excel
     */
    public function exportEmployees(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = Employee::with('plan');

        // Apply same filters as getEmployees
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $employees = $query->get();

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Mobile');
        $sheet->setCellValue('E1', 'Gender');
        $sheet->setCellValue('F1', 'DOB');
        $sheet->setCellValue('G1', 'Plan');
        $sheet->setCellValue('H1', 'Plan Status');
        $sheet->setCellValue('I1', 'Plan Expires');
        $sheet->setCellValue('J1', 'Account Status');
        $sheet->setCellValue('K1', 'Joined Date');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($employees as $employee) {
            $sheet->setCellValue('A' . $row, $employee->id);
            $sheet->setCellValue('B' . $row, $employee->name);
            $sheet->setCellValue('C' . $row, $employee->email);
            $sheet->setCellValue('D' . $row, $employee->mobile);
            $sheet->setCellValue('E' . $row, $employee->gender);
            $sheet->setCellValue('F' . $row, $employee->dob);
            $sheet->setCellValue('G' . $row, $employee->plan ? $employee->plan->name : 'No Plan');

            // Plan status
            $planStatus = 'Inactive';
            if ($employee->plan_is_active) {
                if ($employee->plan_expires_at && Carbon::parse($employee->plan_expires_at)->isPast()) {
                    $planStatus = 'Expired';
                } else {
                    $planStatus = 'Active';
                }
            }
            $sheet->setCellValue('H' . $row, $planStatus);

            $sheet->setCellValue('I' . $row, $employee->plan_expires_at ? Carbon::parse($employee->plan_expires_at)->format('Y-m-d') : 'Never');
            $sheet->setCellValue('J' . $row, ucfirst($employee->account_status ?? 'pending'));
            $sheet->setCellValue('K' . $row, Carbon::parse($employee->created_at)->format('Y-m-d'));

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'employees_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get all employers (Employer Manager / Super Admin)
     */
    public function getEmployers(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = Employer::with('plan.features', 'industry');

        // Search by company_name, email, or contact
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%");
            });
        }

        // Filter by date range (created_at)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by plan_id
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filter by account_status
        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $employers = $query->paginate($request->input('per_page', 20));

        return response()->json(['employers' => $employers], 200);
    }

    /**
     * Get single employer
     */
    public function getEmployer(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employer = Employer::with('plan.features', 'industry', 'jobs')->find($id);

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
        $this->authorizeRole($request, ['super_admin', 'manager']);

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
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employer = Employer::find($id);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $employer->delete();

        return response()->json(['message' => 'Employer deleted'], 200);
    }

    /**
     * Approve employer account
     */
    public function approveEmployer(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $employer = Employer::find($id);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $employer->update(['account_status' => 'approved']);

        return response()->json(['message' => 'Employer approved successfully'], 200);
    }

    /**
     * Export employers to Excel
     */
    public function exportEmployers(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = Employer::with('plan', 'industry');

        // Apply same filters as getEmployers
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->account_status);
        }

        $employers = $query->get();

        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Company Name');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Contact');
        $sheet->setCellValue('E1', 'Industry');
        $sheet->setCellValue('F1', 'Website');
        $sheet->setCellValue('G1', 'Plan');
        $sheet->setCellValue('H1', 'Plan Status');
        $sheet->setCellValue('I1', 'Plan Expires');
        $sheet->setCellValue('J1', 'Account Status');
        $sheet->setCellValue('K1', 'Joined Date');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($employers as $employer) {
            $sheet->setCellValue('A' . $row, $employer->id);
            $sheet->setCellValue('B' . $row, $employer->company_name);
            $sheet->setCellValue('C' . $row, $employer->email);
            $sheet->setCellValue('D' . $row, $employer->contact);
            $sheet->setCellValue('E' . $row, $employer->industry ? $employer->industry->name : 'N/A');
            $sheet->setCellValue('F' . $row, $employer->website ?? 'N/A');
            $sheet->setCellValue('G' . $row, $employer->plan ? $employer->plan->name : 'No Plan');

            // Plan status
            $planStatus = 'Inactive';
            if ($employer->plan_is_active) {
                if ($employer->plan_expires_at && Carbon::parse($employer->plan_expires_at)->isPast()) {
                    $planStatus = 'Expired';
                } else {
                    $planStatus = 'Active';
                }
            }
            $sheet->setCellValue('H' . $row, $planStatus);

            $sheet->setCellValue('I' . $row, $employer->plan_expires_at ? Carbon::parse($employer->plan_expires_at)->format('Y-m-d') : 'Never');
            $sheet->setCellValue('J' . $row, ucfirst($employer->account_status ?? 'pending'));
            $sheet->setCellValue('K' . $row, Carbon::parse($employer->created_at)->format('Y-m-d'));

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'employers_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Get all jobs (Super Admin / Employer Manager)
     */
    public function getJobs(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $jobs = Job::with(['employer', 'location', 'category'])->paginate(50);

        return response()->json(['jobs' => $jobs], 200);
    }


    /**
     * Add manual commission (Super Admin only)
     */
    public function addManualCommission(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:admins,id',
            'amount_earned' => 'required|numeric|min:0',
            'payment_id' => 'nullable|exists:payments,id',
            'order_id' => 'nullable|exists:plan_orders,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'transaction_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $commission = CommissionTransaction::create([
            'staff_id' => $request->staff_id,
            'payment_id' => $request->payment_id,
            'order_id' => $request->order_id,
            'coupon_id' => $request->coupon_id,
            'amount_earned' => $request->amount_earned,
            'transaction_amount' => $request->transaction_amount,
            'discount_amount' => $request->discount_amount,
            'discount_percentage' => $request->discount_percentage,
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

        $query = CommissionTransaction::with(['staff', 'payment', 'order', 'coupon']);

        // Filter by staff_id
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by type (coupon_based or manual)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by coupon_id
        if ($request->filled('coupon_id')) {
            $query->where('coupon_id', $request->coupon_id);
        }

        $commissions = $query->latest()->paginate(50);

        // Calculate total earnings
        $totalEarnings = $query->sum('amount_earned');

        return response()->json([
            'commissions' => $commissions,
            'total_earnings' => $totalEarnings,
        ], 200);
    }

    /**
     * View staff member's commissions
     */
    public function getStaffCommissions(Request $request)
    {
        $admin = $request->user();

        $commissions = CommissionTransaction::where('staff_id', $admin->id)
            ->with(['payment', 'order', 'coupon'])
            ->latest()
            ->get();

        $totalEarned = $commissions->sum('amount_earned');

        return response()->json([
            'commissions' => $commissions,
            'total_earned' => $totalEarned,
        ], 200);
    }

    /**
     * View manager's commissions (Manager can see their own and their staff's commissions)
     */
    public function getManagerCommissions(Request $request)
    {
        $this->authorizeRole($request, ['manager']);

        $admin = $request->user();

        // Get manager's own commissions and commissions of staff under this manager
        $staffIds = Admin::where('manager_id', $admin->id)->pluck('id')->toArray();
        $staffIds[] = $admin->id; // Include manager's own commissions

        $query = CommissionTransaction::whereIn('staff_id', $staffIds)
            ->with(['staff', 'payment', 'order', 'coupon']);

        // Filter by staff_id
        if ($request->filled('staff_id') && in_array($request->staff_id, $staffIds)) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by type (coupon_based or manual)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $commissions = $query->latest()->paginate(50);

        // Calculate total earnings
        $totalEarnings = CommissionTransaction::whereIn('staff_id', $staffIds)->sum('amount_earned');

        // Get staff list
        $staffList = Admin::whereIn('id', $staffIds)->select('id', 'name', 'email', 'role')->get();

        return response()->json([
            'commissions' => $commissions,
            'total_earnings' => $totalEarnings,
            'staff_list' => $staffList,
        ], 200);
    }

    /**
     * Get all admins (Super Admin only)
     */
    public function getAdmins(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $query = Admin::with('manager');

        // Filter by role (staff or manager)
        if ($request->has('role') && in_array($request->role, ['staff', 'manager'])) {
            $query->where('role', $request->role);
        }

        // Filter by manager_id (find staff assigned to a specific manager)
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        $admins = $query->latest()->paginate(50);

        return response()->json(['admins' => $admins], 200);
    }

    /**
     * Get single admin (Super Admin only)
     */
    public function getAdmin(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin']);

        $admin = Admin::with(['manager', 'staff'])->find($id);

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
            'role' => 'required|in:super_admin,manager,staff',
            'manager_id' => 'nullable|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If manager_id is provided and role is staff, validate the manager
        if ($request->manager_id && $request->role === 'staff') {
            $manager = Admin::find($request->manager_id);
            if (!$manager || $manager->role !== 'manager') {
                return response()->json(['message' => 'Invalid manager. The selected admin must have manager role'], 400);
            }
        }

        // Only staff can have a manager
        if ($request->manager_id && $request->role !== 'staff') {
            return response()->json(['message' => 'Only staff members can be assigned to a manager'], 400);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Will be hashed by model mutator
            'role' => $request->role,
            'manager_id' => ($request->role === 'staff') ? $request->manager_id : null,
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin->load('manager'),
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
            'role' => 'sometimes|in:super_admin,manager,staff',
            'password' => 'sometimes|string|min:8',
            'manager_id' => 'nullable|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only(['name', 'email', 'role']);

        // Handle manager_id based on role
        if ($request->has('manager_id')) {
            $newRole = $request->role ?? $admin->role;

            if ($request->manager_id && $newRole === 'staff') {
                $manager = Admin::find($request->manager_id);
                if (!$manager || $manager->role !== 'manager') {
                    return response()->json(['message' => 'Invalid manager. The selected admin must have manager role'], 400);
                }
                $updateData['manager_id'] = $request->manager_id;
            } elseif ($request->manager_id && $newRole !== 'staff') {
                return response()->json(['message' => 'Only staff members can be assigned to a manager'], 400);
            } else {
                // If manager_id is null, unassign
                $updateData['manager_id'] = null;
            }
        }

        // If role is changing from staff to manager/super_admin, clear manager_id
        if ($request->has('role') && $request->role !== 'staff') {
            $updateData['manager_id'] = null;
        }

        if ($request->has('password')) {
            $updateData['password'] = $request->password; // Will be hashed by model mutator
        }

        $admin->update($updateData);

        return response()->json([
            'message' => 'Admin updated successfully',
            'admin' => $admin->fresh()->load('manager'),
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
     * Assign staff to a manager (Super Admin only)
     */
    public function assignStaffToManager(Request $request, $staffId)
    {
        $this->authorizeRole($request, ['super_admin']);

        $validator = Validator::make($request->all(), [
            'manager_id' => 'nullable|exists:admins,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staff = Admin::find($staffId);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        // Only staff role can be assigned to a manager
        if ($staff->role !== 'staff') {
            return response()->json(['message' => 'Only staff members can be assigned to a manager'], 400);
        }

        // If manager_id is provided, validate that the admin is a manager
        if ($request->manager_id) {
            $manager = Admin::find($request->manager_id);

            if (!$manager) {
                return response()->json(['message' => 'Manager not found'], 404);
            }

            if ($manager->role !== 'manager') {
                return response()->json(['message' => 'The selected admin must have manager role'], 400);
            }
        }

        // Update staff's manager
        $staff->update(['manager_id' => $request->manager_id]);

        return response()->json([
            'message' => $request->manager_id
                ? 'Staff assigned to manager successfully'
                : 'Staff unassigned from manager successfully',
            'staff' => $staff->fresh()->load('manager'),
        ], 200);
    }

    /**
     * Get all managers (Super Admin only)
     */
    public function getManagers(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $managers = Admin::where('role', 'manager')
            ->withCount('staff')
            ->latest()
            ->get();

        return response()->json(['managers' => $managers], 200);
    }

    /**
     * Get all CV requests (Employee Manager / Super Admin)
     */
    public function getCVRequests(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $cvRequests = CVRequest::with('employee')->latest()->paginate(20);

        return response()->json(['cv_requests' => $cvRequests], 200);
    }

    /**
     * Update CV request status (Employee Manager / Super Admin)
     */
    public function updateCVRequestStatus(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

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
     * Upgrade employee plan (Admin: Super Admin / Plan Upgrade Manager)
     */
    public function upgradeEmployeePlan(Request $request, $employeeId)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'payment_id' => 'nullable|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $newPlan = Plan::find($request->plan_id);

        // Check if plan is for employees
        if ($newPlan->type !== 'employee') {
            return response()->json([
                'message' => 'Invalid plan type. Must be an employee plan',
            ], 400);
        }

        // Calculate new plan dates
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($newPlan->validity_days);

        // Mark current subscription as cancelled if exists
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
            'message' => 'Employee plan upgraded successfully',
            'employee' => $employee->fresh()->load('plan'),
            'subscription_id' => $subscription->id,
        ], 200);
    }

    /**
     * Upgrade employer plan (Admin: Super Admin / Plan Upgrade Manager)
     */
    public function upgradeEmployerPlan(Request $request, $employerId)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'payment_id' => 'nullable|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employer = Employer::find($employerId);

        if (!$employer) {
            return response()->json(['message' => 'Employer not found'], 404);
        }

        $newPlan = Plan::find($request->plan_id);

        // Check if plan is for employers
        if ($newPlan->type !== 'employer') {
            return response()->json([
                'message' => 'Invalid plan type. Must be an employer plan',
            ], 400);
        }

        // Calculate new plan dates
        $planStartedAt = Carbon::now();
        $planExpiresAt = $planStartedAt->copy()->addDays($newPlan->validity_days);

        // Mark current subscription as cancelled if exists
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
        ]);

        return response()->json([
            'message' => 'Employer plan upgraded successfully',
            'employer' => $employer->fresh()->load('plan'),
            'subscription_id' => $subscription->id,
        ], 200);
    }

    /**
     * Get profile photos with optional status filter
     */
    public function getProfilePhotos(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'admin', 'staff']);

        // Validate status filter
        $status = $request->query('status', 'pending');

        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'])) {
            return response()->json(['message' => 'Invalid status filter'], 400);
        }

        $query = \App\Models\Employee::whereNotNull('profile_photo_url')
            ->select('id', 'name', 'email', 'mobile', 'profile_photo_url', 'profile_photo_status', 'profile_photo_rejection_reason', 'created_at', 'updated_at');

        // Apply status filter
        if ($status !== 'all') {
            $query->where('profile_photo_status', $status);
        }

        $employees = $query->latest('updated_at')->get();

        // The profile_photo_full_url accessor will be automatically appended
        return response()->json([
            'employees' => $employees,
            'status' => $status,
            'count' => $employees->count(),
        ], 200);
    }

    /**
     * Get pending profile photos for approval (backward compatibility)
     */
    public function getPendingProfilePhotos(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'admin', 'staff']);

        $employees = \App\Models\Employee::where('profile_photo_status', 'pending')
            ->whereNotNull('profile_photo_url')
            ->select('id', 'name', 'email', 'mobile', 'profile_photo_url', 'profile_photo_status', 'created_at')
            ->latest('updated_at')
            ->get();

        // The profile_photo_full_url accessor will be automatically appended
        return response()->json([
            'employees' => $employees,
        ], 200);
    }

    /**
     * Approve or reject profile photo
     */
    public function updateProfilePhotoStatus(Request $request, $employeeId)
    {
        $this->authorizeRole($request, ['super_admin', 'admin', 'staff']);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee = \App\Models\Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        if ($employee->profile_photo_status !== 'pending') {
            return response()->json(['message' => 'This profile photo is not pending approval'], 400);
        }

        $employee->update([
            'profile_photo_status' => $request->status,
            'profile_photo_rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
        ]);

        // Refresh employee to get updated accessor values
        $employee->refresh();

        return response()->json([
            'message' => 'Profile photo ' . $request->status . ' successfully',
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'profile_photo_url' => $employee->profile_photo_full_url,
                'profile_photo_status' => $employee->profile_photo_status,
            ],
        ], 200);
    }

    /**
     * Get all plan orders (Super Admin / Plan Upgrade Manager)
     */
    public function getPlanOrders(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = PlanOrder::with(['plan', 'employee', 'employer', 'transaction', 'coupon']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user type
        if ($request->has('user_type')) {
            if ($request->user_type === 'employee') {
                $query->whereNotNull('employee_id');
            } elseif ($request->user_type === 'employer') {
                $query->whereNotNull('employer_id');
            }
        }

        // Search by order ID or razorpay order ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('razorpay_order_id', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json(['orders' => $orders], 200);
    }

    /**
     * Get single plan order details (Super Admin / Plan Upgrade Manager)
     */
    public function getPlanOrder(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $order = PlanOrder::with(['plan', 'employee', 'employer', 'transaction', 'coupon'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['order' => $order], 200);
    }

    /**
     * Get all payment transactions (Super Admin / Plan Upgrade Manager)
     */
    public function getPaymentTransactions(Request $request)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $query = PaymentTransaction::with(['order.plan', 'order.employee', 'order.employer', 'order.coupon']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        // Search by transaction ID or razorpay payment ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('razorpay_payment_id', 'like', "%{$search}%")
                  ->orWhere('razorpay_order_id', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json(['transactions' => $transactions], 200);
    }

    /**
     * Get single payment transaction details (Super Admin / Plan Upgrade Manager)
     */
    public function getPaymentTransaction(Request $request, $id)
    {
        $this->authorizeRole($request, ['super_admin', 'manager']);

        $transaction = PaymentTransaction::with(['order.plan', 'order.employee', 'order.employer', 'order.coupon'])->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json(['transaction' => $transaction], 200);
    }

    /**
     * Get payment statistics (Super Admin)
     */
    public function getPaymentStats(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $stats = [
            'total_orders' => PlanOrder::count(),
            'total_paid_orders' => PlanOrder::where('status', 'paid')->count(),
            'total_pending_orders' => PlanOrder::where('status', 'created')->count(),
            'total_failed_orders' => PlanOrder::where('status', 'failed')->count(),
            'total_revenue' => (string) PaymentTransaction::where('status', 'success')->sum('amount'),
            'total_transactions' => PaymentTransaction::count(),
            'successful_transactions' => PaymentTransaction::where('status', 'success')->count(),
            'failed_transactions' => PaymentTransaction::where('status', 'failed')->count(),
        ];

        // Revenue by month (last 12 months)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = PaymentTransaction::where('status', 'success')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'revenue' => (string) $revenue,
            ];
        }
        $stats['monthly_revenue'] = $monthlyRevenue;

        return response()->json($stats, 200);
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

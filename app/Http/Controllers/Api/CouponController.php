<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    /**
     * Create a new coupon (Staff, Manager, Super Admin)
     */
    public function createCoupon(Request $request)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin', 'manager', 'staff']);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:191|unique:coupons,code',
            'name' => 'required|string|max:191',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'coupon_for' => 'required|in:employee,employer',
            'expiry_date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'discount_percentage' => $request->discount_percentage,
            'coupon_for' => $request->coupon_for,
            'expiry_date' => $request->expiry_date,
            'created_by' => $admin->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Coupon created successfully and pending approval',
            'coupon' => $coupon->load('creator'),
        ], 201);
    }

    /**
     * Get all coupons (with role-based filtering)
     */
    public function getCoupons(Request $request)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin', 'manager', 'staff']);

        $query = Coupon::with(['creator', 'approver']);

        // Role-based filtering
        if ($admin->role === 'staff') {
            // Staff can only see their own coupons
            $query->where('created_by', $admin->id);
        } elseif ($admin->role === 'manager') {
            // Manager can see their own and their assigned staff's coupons
            $staffIds = Admin::where('manager_id', $admin->id)->pluck('id')->toArray();
            $staffIds[] = $admin->id; // Include manager's own coupons
            $query->whereIn('created_by', $staffIds);
        }
        // Super admin sees all coupons (no filter needed)

        // Optional status filter
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // Optional coupon_for filter
        if ($request->has('coupon_for') && in_array($request->coupon_for, ['employee', 'employer'])) {
            $query->where('coupon_for', $request->coupon_for);
        }

        $coupons = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'coupons' => $coupons,
            'count' => $coupons->count(),
        ], 200);
    }

    /**
     * Get single coupon details
     */
    public function getCoupon(Request $request, $id)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin', 'manager', 'staff']);

        $coupon = Coupon::with(['creator', 'approver', 'assignedUsers.assigner'])->find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        // Check if admin has permission to view this coupon
        if ($admin->role === 'staff' && $coupon->created_by !== $admin->id) {
            return response()->json(['message' => 'Unauthorized to view this coupon'], 403);
        } elseif ($admin->role === 'manager') {
            $staffIds = Admin::where('manager_id', $admin->id)->pluck('id')->toArray();
            $staffIds[] = $admin->id;
            if (!in_array($coupon->created_by, $staffIds)) {
                return response()->json(['message' => 'Unauthorized to view this coupon'], 403);
            }
        }

        // Load assigned users with details
        $assignedUsers = CouponUser::where('coupon_id', $id)
            ->with('assigner')
            ->get()
            ->map(function ($assignment) {
                $userData = null;
                if ($assignment->user_type === 'employee') {
                    $userData = Employee::find($assignment->user_id);
                } elseif ($assignment->user_type === 'employer') {
                    $userData = Employer::find($assignment->user_id);
                }

                return [
                    'id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'user_type' => $assignment->user_type,
                    'user_data' => $userData ? [
                        'id' => $userData->id,
                        'name' => $userData->name ?? $userData->company_name ?? 'N/A',
                        'email' => $userData->email,
                        'mobile' => $userData->mobile ?? $userData->contact ?? null,
                    ] : null,
                    'assigned_by' => $assignment->assigner,
                    'assigned_at' => $assignment->assigned_at,
                ];
            });

        return response()->json([
            'coupon' => $coupon,
            'assigned_users' => $assignedUsers,
        ], 200);
    }

    /**
     * Approve or reject coupon (Super Admin only)
     */
    public function approveCoupon(Request $request, $id)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin']);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        if ($coupon->status !== 'pending') {
            return response()->json(['message' => 'Only pending coupons can be approved or rejected'], 400);
        }

        $coupon->update([
            'status' => $request->status,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Coupon ' . $request->status . ' successfully',
            'coupon' => $coupon->fresh()->load(['creator', 'approver']),
        ], 200);
    }

    /**
     * Assign users to approved coupon (Staff, Manager, Super Admin)
     */
    public function assignUsers(Request $request, $id)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin', 'manager', 'staff']);

        $validator = Validator::make($request->all(), [
            'users' => 'required|array|min:1',
            'users.*.identifier' => 'required|string', // Email or phone
            'users.*.type' => 'required|in:employee,employer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        // Check if coupon is approved
        if ($coupon->status !== 'approved') {
            return response()->json(['message' => 'Only approved coupons can have users assigned'], 400);
        }

        // Check if admin has permission to assign users to this coupon
        if ($admin->role === 'staff' && $coupon->created_by !== $admin->id) {
            return response()->json(['message' => 'Unauthorized to assign users to this coupon'], 403);
        } elseif ($admin->role === 'manager') {
            $staffIds = Admin::where('manager_id', $admin->id)->pluck('id')->toArray();
            $staffIds[] = $admin->id;
            if (!in_array($coupon->created_by, $staffIds)) {
                return response()->json(['message' => 'Unauthorized to assign users to this coupon'], 403);
            }
        }

        $assigned = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($request->users as $userInput) {
                $identifier = $userInput['identifier'];
                $type = $userInput['type'];

                // Find user by email or phone
                $user = null;
                if ($type === 'employee') {
                    $user = Employee::where('email', $identifier)
                        ->orWhere('mobile', $identifier)
                        ->first();
                } elseif ($type === 'employer') {
                    $user = Employer::where('email', $identifier)
                        ->orWhere('contact', $identifier)
                        ->first();
                }

                if (!$user) {
                    $failed[] = [
                        'identifier' => $identifier,
                        'type' => $type,
                        'reason' => 'User not found',
                    ];
                    continue;
                }

                // Check coupon type matches user type
                if ($coupon->coupon_for !== $type) {
                    $failed[] = [
                        'identifier' => $identifier,
                        'type' => $type,
                        'reason' => "This coupon is only for {$coupon->coupon_for}s",
                    ];
                    continue;
                }

                // Check if already assigned
                $existingAssignment = CouponUser::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->where('user_type', $type)
                    ->first();

                if ($existingAssignment) {
                    $failed[] = [
                        'identifier' => $identifier,
                        'type' => $type,
                        'reason' => 'User already assigned to this coupon',
                    ];
                    continue;
                }

                // Assign user to coupon
                $assignment = CouponUser::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'user_type' => $type,
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                ]);

                $assigned[] = [
                    'id' => $assignment->id,
                    'user_id' => $user->id,
                    'user_type' => $type,
                    'user_name' => $user->name ?? $user->company_name ?? 'N/A',
                    'user_email' => $user->email,
                ];
            }

            DB::commit();

            return response()->json([
                'message' => 'User assignment completed',
                'assigned' => $assigned,
                'failed' => $failed,
                'assigned_count' => count($assigned),
                'failed_count' => count($failed),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error assigning users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove user from coupon
     */
    public function removeUser(Request $request, $couponId, $assignmentId)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin', 'manager', 'staff']);

        $coupon = Coupon::find($couponId);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        // Check if admin has permission
        if ($admin->role === 'staff' && $coupon->created_by !== $admin->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } elseif ($admin->role === 'manager') {
            $staffIds = Admin::where('manager_id', $admin->id)->pluck('id')->toArray();
            $staffIds[] = $admin->id;
            if (!in_array($coupon->created_by, $staffIds)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $assignment = CouponUser::where('id', $assignmentId)
            ->where('coupon_id', $couponId)
            ->first();

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        $assignment->delete();

        return response()->json([
            'message' => 'User removed from coupon successfully',
        ], 200);
    }

    /**
     * Delete coupon (Super Admin only, only if no users assigned)
     */
    public function deleteCoupon(Request $request, $id)
    {
        $admin = $request->user();
        $this->authorizeRole($request, ['super_admin']);

        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        // Check if any users are assigned
        $assignedCount = CouponUser::where('coupon_id', $id)->count();

        if ($assignedCount > 0) {
            return response()->json([
                'message' => 'Cannot delete coupon with assigned users. Please remove all users first.',
            ], 400);
        }

        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ], 200);
    }

    /**
     * Get pending coupons for approval (Super Admin only)
     */
    public function getPendingCoupons(Request $request)
    {
        $this->authorizeRole($request, ['super_admin']);

        $coupons = Coupon::where('status', 'pending')
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'coupons' => $coupons,
            'count' => $coupons->count(),
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

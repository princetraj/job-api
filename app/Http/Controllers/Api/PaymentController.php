<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\CommissionTransaction;
use App\Models\PlanOrder;
use App\Models\PaymentTransaction;
use App\Models\EmployeePlanSubscription;
use App\Models\EmployerPlanSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Subscribe to a plan (with optional coupon)
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'coupon_code' => 'nullable|string|exists:coupons,code',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $plan = Plan::find($request->plan_id);

        // Calculate amount
        $originalAmount = $plan->price;
        $discount = 0;
        $finalAmount = $originalAmount;
        $coupon = null;

        // Apply coupon if provided
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)
                ->where('expiry_date', '>=', now())
                ->first();

            if ($coupon) {
                $discount = ($originalAmount * $coupon->discount_percentage) / 100;
                $finalAmount = $originalAmount - $discount;
            }
        }

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'plan_id' => $plan->id,
                'amount' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discount,
                'coupon_id' => $coupon ? $coupon->id : null,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'transaction_id' => 'TXN_' . time() . '_' . rand(1000, 9999),
            ]);

            // If coupon was used, create commission transaction
            if ($coupon) {
                $commissionPercentage = 10; // 10% commission (can be configured)
                $commissionAmount = ($finalAmount * $commissionPercentage) / 100;

                CommissionTransaction::create([
                    'staff_id' => $coupon->staff_id,
                    'payment_id' => $payment->id,
                    'amount_earned' => $commissionAmount,
                    'type' => 'coupon_based',
                ]);
            }

            // Update payment to completed (in production, this would be done via payment gateway webhook)
            $payment->update([
                'payment_status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update user's plan
            $user->update(['plan_id' => $plan->id]);

            DB::commit();

            $expiresAt = now()->addDays($plan->validity_days);

            return response()->json([
                'message' => 'Subscription successful',
                'payment' => [
                    'id' => $payment->id,
                    'user_type' => $payment->user_type === 'App\\Models\\Employee' ? 'employee' : 'employer',
                    'user_id' => $payment->user_id,
                    'plan_id' => $payment->plan_id,
                    'amount' => number_format($finalAmount, 2, '.', ''),
                    'discount_amount' => number_format($discount, 2, '.', ''),
                    'final_amount' => number_format($finalAmount, 2, '.', ''),
                    'coupon_code' => $request->coupon_code ?? null,
                    'payment_status' => $payment->payment_status,
                    'transaction_id' => $payment->transaction_id,
                    'created_at' => $payment->created_at,
                ],
                'subscription_expires_at' => $expiresAt,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment and update subscription
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::find($request->payment_id);

        if ($payment->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // In production, verify with actual payment gateway
        // For now, we'll mark as completed

        $payment->update([
            'payment_status' => 'completed',
            'transaction_id' => $request->transaction_id,
            'paid_at' => now(),
        ]);

        // Update user's plan
        $user = $request->user();
        $user->update([
            'plan_id' => $payment->plan_id,
        ]);

        return response()->json([
            'message' => 'Payment verified',
            'payment' => [
                'id' => $payment->id,
                'payment_status' => $payment->payment_status,
                'amount' => $payment->amount,
            ],
        ], 200);
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Request $request)
    {
        $user = $request->user();

        $payments = Payment::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->with('plan')
            ->latest()
            ->paginate(20);

        return response()->json([
            'payments' => $payments,
        ], 200);
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(Request $request)
    {
        try {
            $user = $request->user();

            // User should be authenticated via auth:sanctum middleware
            // If we reach here without a user, something is wrong
            if (!$user) {
                \Log::error('ValidateCoupon: User not found despite auth middleware');
                return response()->json([
                    'valid' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::where('code', strtoupper($request->coupon_code))
            ->where('status', 'approved')
            ->where('expiry_date', '>=', now())
            ->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid, expired, or not approved coupon code',
            ], 200);
        }

        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return response()->json([
                'valid' => false,
                'message' => 'Plan not found',
            ], 200);
        }

        // Determine user type
        $userType = null;
        $userId = $user->id;

        if ($user instanceof \App\Models\Employee) {
            $userType = 'employee';
        } elseif ($user instanceof \App\Models\Employer) {
            $userType = 'employer';
        } else {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid user type',
            ], 200);
        }

        // Check if coupon is for the right user type
        if ($coupon->coupon_for !== $userType) {
            return response()->json([
                'valid' => false,
                'message' => "This coupon is only valid for {$coupon->coupon_for}s",
            ], 200);
        }

        // Check if user is assigned to this coupon
        $isAssigned = \App\Models\CouponUser::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->exists();

        if (!$isAssigned) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon is not available for your account',
            ], 200);
        }

        // Check if plan type matches coupon type
        if ($plan->type !== $userType) {
            return response()->json([
                'valid' => false,
                'message' => 'Plan type does not match coupon type',
            ], 200);
        }

        $discount = ($plan->price * $coupon->discount_percentage) / 100;
        $finalAmount = $plan->price - $discount;

            return response()->json([
                'valid' => true,
                'coupon' => [
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'discount_percentage' => $coupon->discount_percentage,
                    'expiry_date' => $coupon->expiry_date,
                ],
                'plan' => [
                    'price' => $plan->price,
                ],
                'discount_amount' => number_format($discount, 2, '.', ''),
                'final_amount' => number_format($finalAmount, 2, '.', ''),
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Coupon validation error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'An error occurred while validating the coupon. Please try again.',
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 200); // Return 200 to avoid logout, with valid: false
        }
    }

    /**
     * Create Razorpay order for plan upgrade (with optional coupon)
     */
    public function createRazorpayOrder(Request $request)
    {
        \Log::info('CreateRazorpayOrder: Starting order creation', [
            'user_id' => $request->user()->id ?? 'unknown',
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('CreateRazorpayOrder: Validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            \Log::error('CreateRazorpayOrder: Plan not found', ['plan_id' => $request->plan_id]);
            return response()->json([
                'message' => 'Plan not found',
            ], 404);
        }

        // Check if plan is for the correct user type
        $userType = class_basename(get_class($user));
        if (strtolower($userType) !== $plan->type) {
            return response()->json([
                'message' => 'Invalid plan type for your account',
            ], 400);
        }

        // Check if trying to upgrade to default plan
        if ($plan->is_default) {
            return response()->json([
                'message' => 'Cannot upgrade to default plan',
            ], 400);
        }

        // Handle coupon validation and discount calculation
        $originalAmount = $plan->price;
        $discountAmount = 0;
        $finalAmount = $originalAmount;
        $coupon = null;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))
                ->where('status', 'approved')
                ->where('expiry_date', '>=', now())
                ->first();

            if (!$coupon) {
                return response()->json([
                    'message' => 'Invalid, expired, or not approved coupon code',
                ], 400);
            }

            // Check if coupon is for the right user type
            if ($coupon->coupon_for !== strtolower($userType)) {
                return response()->json([
                    'message' => "This coupon is only valid for {$coupon->coupon_for}s",
                ], 400);
            }

            // Check if user is assigned to this coupon
            $isAssigned = \App\Models\CouponUser::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->where('user_type', strtolower($userType))
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'message' => 'This coupon is not available for your account',
                ], 400);
            }

            // Calculate discount
            $discountAmount = ($originalAmount * $coupon->discount_percentage) / 100;
            $finalAmount = $originalAmount - $discountAmount;
        }

        \Log::info('CreateRazorpayOrder: Coupon validation passed', [
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'coupon_code' => $coupon ? $coupon->code : null
        ]);

        DB::beginTransaction();
        try {
            // Initialize Razorpay API
            $razorpayKey = config('services.razorpay.key');
            $razorpaySecret = config('services.razorpay.secret');

            if (empty($razorpayKey) || empty($razorpaySecret)) {
                \Log::error('CreateRazorpayOrder: Razorpay credentials not configured');
                throw new \Exception('Payment gateway not configured. Please contact administrator.');
            }

            \Log::info('CreateRazorpayOrder: Initializing Razorpay API');
            $api = new Api($razorpayKey, $razorpaySecret);

            // Convert amount to paise (Razorpay requires amount in smallest currency unit)
            $amountInPaise = (int)($finalAmount * 100);

            \Log::info('CreateRazorpayOrder: Creating Razorpay order', [
                'amount_in_paise' => $amountInPaise,
                'amount_in_rupees' => $finalAmount
            ]);

            // Create Razorpay order
            $razorpayOrder = $api->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'order_' . time(),
                'notes' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'user_type' => $userType,
                    'user_id' => $user->id,
                    'coupon_code' => $coupon ? $coupon->code : null,
                    'discount_amount' => $discountAmount,
                ]
            ]);

            \Log::info('CreateRazorpayOrder: Razorpay order created successfully', [
                'razorpay_order_id' => $razorpayOrder['id']
            ]);

            // Store order in database
            \Log::info('CreateRazorpayOrder: Saving order to database');
            $order = PlanOrder::create([
                'employee_id' => $userType === 'Employee' ? $user->id : null,
                'employer_id' => $userType === 'Employer' ? $user->id : null,
                'plan_id' => $plan->id,
                'coupon_id' => $coupon ? $coupon->id : null,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'currency' => 'INR',
                'status' => 'created',
                'notes' => $coupon ? "Plan upgrade with coupon: {$coupon->code}" : 'Plan upgrade order',
            ]);

            \Log::info('CreateRazorpayOrder: Order saved successfully', ['order_id' => $order->id]);

            DB::commit();

            return response()->json([
                'order_id' => $order->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'currency' => 'INR',
                'razorpay_key' => config('services.razorpay.key'),
                'coupon_applied' => $coupon ? [
                    'code' => $coupon->code,
                    'discount_percentage' => $coupon->discount_percentage,
                ] : null,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('CreateRazorpayOrder: Order creation failed', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown',
                'plan_id' => $request->plan_id ?? 'unknown',
                'coupon_code' => $request->coupon_code ?? null
            ]);

            // Check for specific error types
            $errorMessage = 'Failed to create order';
            if (strpos($e->getMessage(), 'Authentication') !== false) {
                $errorMessage = 'Payment gateway authentication failed. Please contact support.';
            } elseif (strpos($e->getMessage(), 'amount') !== false) {
                $errorMessage = 'Invalid payment amount. Please try again.';
            } elseif (strpos($e->getMessage(), 'SQLSTATE') !== false || strpos($e->getMessage(), 'database') !== false) {
                $errorMessage = 'Database error. Please try again or contact support.';
            }

            return response()->json([
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request',
                'error_code' => $e->getCode()
            ], 500);
        }
    }

    /**
     * Verify Razorpay payment and activate plan
     */
    public function verifyRazorpayPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        DB::beginTransaction();
        try {
            // Find the order
            $order = PlanOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Verify that the order belongs to the authenticated user
            $userType = class_basename(get_class($user));
            if (($userType === 'Employee' && $order->employee_id !== $user->id) ||
                ($userType === 'Employer' && $order->employer_id !== $user->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Initialize Razorpay API
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            // Verify signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Fetch payment details from Razorpay
            $payment = $api->payment->fetch($request->razorpay_payment_id);

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_signature' => $request->razorpay_signature,
                'amount' => $payment['amount'] / 100, // Convert from paise to rupees
                'currency' => $payment['currency'],
                'status' => 'success',
                'method' => $payment['method'],
                'payment_details' => $payment->toArray(),
            ]);

            // Update order status
            $order->update(['status' => 'paid']);

            // Get the plan
            $plan = Plan::find($order->plan_id);

            // Calculate plan dates
            $planStartedAt = Carbon::now();
            $planExpiresAt = $planStartedAt->copy()->addDays($plan->validity_days);

            // Cancel existing active subscriptions
            if ($userType === 'Employee') {
                EmployeePlanSubscription::where('employee_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                // Update employee plan details
                $user->update([
                    'plan_id' => $plan->id,
                    'plan_started_at' => $planStartedAt,
                    'plan_expires_at' => $planExpiresAt,
                    'plan_is_active' => true,
                ]);

                // Create new subscription record
                $subscription = EmployeePlanSubscription::create([
                    'employee_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_id' => null,
                    'started_at' => $planStartedAt,
                    'expires_at' => $planExpiresAt,
                    'status' => 'active',
                    'is_default' => false,
                    'jobs_remaining' => $plan->jobs_can_apply,
                    'contact_views_remaining' => $plan->contact_details_can_view,
                ]);

            } else if ($userType === 'Employer') {
                EmployerPlanSubscription::where('employer_id', $user->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                // Update employer plan details
                $user->update([
                    'plan_id' => $plan->id,
                    'plan_started_at' => $planStartedAt,
                    'plan_expires_at' => $planExpiresAt,
                    'plan_is_active' => true,
                ]);

                // Create new subscription record
                $subscription = EmployerPlanSubscription::create([
                    'employer_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_id' => null,
                    'started_at' => $planStartedAt,
                    'expires_at' => $planExpiresAt,
                    'status' => 'active',
                    'is_default' => false,
                    'contact_views_remaining' => $plan->employee_contact_details_can_view,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment verified and plan activated successfully',
                'transaction_id' => $transaction->id,
                'plan' => [
                    'name' => $plan->name,
                    'started_at' => $planStartedAt->toDateTimeString(),
                    'expires_at' => $planExpiresAt->toDateTimeString(),
                ],
                'subscription_id' => $subscription->id ?? null,
            ], 200);

        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            DB::rollBack();

            // Update order status to failed
            if (isset($order)) {
                $order->update(['status' => 'failed']);

                // Store failed transaction
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'status' => 'failed',
                    'error_description' => 'Payment signature verification failed',
                ]);
            }

            return response()->json([
                'message' => 'Payment verification failed',
                'error' => 'Invalid payment signature'
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            // Update order status to failed if order exists
            if (isset($order)) {
                $order->update(['status' => 'failed']);
            }

            return response()->json([
                'message' => 'Payment verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails(Request $request, $orderId)
    {
        $user = $request->user();
        $userType = class_basename(get_class($user));

        $order = PlanOrder::with(['plan', 'transaction'])->find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Verify that the order belongs to the authenticated user
        if (($userType === 'Employee' && $order->employee_id !== $user->id) ||
            ($userType === 'Employer' && $order->employer_id !== $user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'order' => $order,
        ], 200);
    }

    /**
     * Get payment transactions history
     */
    public function getTransactionHistory(Request $request)
    {
        $user = $request->user();
        $userType = class_basename(get_class($user));

        $orders = PlanOrder::with(['plan', 'transaction'])
            ->where($userType === 'Employee' ? 'employee_id' : 'employer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ], 200);
    }

    /**
     * Get user's assigned coupons for plan upgrade
     */
    public function getMyAssignedCoupons(Request $request)
    {
        try {
            $user = $request->user();

            // User should be authenticated via auth:sanctum middleware
            if (!$user) {
                \Log::error('GetMyAssignedCoupons: User not found despite auth middleware');
                return response()->json([
                    'message' => 'Authentication required',
                    'coupons' => [],
                    'count' => 0,
                ], 401);
            }

            $userType = class_basename(get_class($user));

        // Get all assigned coupons for this user that are valid
        $assignedCoupons = \App\Models\CouponUser::where('user_id', $user->id)
            ->where('user_type', strtolower($userType))
            ->with(['coupon'])
            ->get()
            ->filter(function ($assignment) {
                // Check if coupon exists and is valid
                if (!$assignment->coupon) {
                    return false;
                }
                try {
                    return $assignment->coupon->isValid();
                } catch (\Exception $e) {
                    return false;
                }
            })
            ->map(function ($assignment) {
                // Additional null check before accessing properties
                if (!$assignment->coupon) {
                    return null;
                }

                return [
                    'id' => $assignment->coupon->id ?? null,
                    'code' => $assignment->coupon->code ?? '',
                    'name' => $assignment->coupon->name ?? '',
                    'discount_percentage' => $assignment->coupon->discount_percentage ?? 0,
                    'expiry_date' => $assignment->coupon->expiry_date ?? null,
                    'coupon_for' => $assignment->coupon->coupon_for ?? '',
                    'assigned_at' => $assignment->assigned_at ?? null,
                ];
            })
            ->filter() // Remove null values
            ->values();

            return response()->json([
                'coupons' => $assignedCoupons,
                'count' => $assignedCoupons->count(),
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching assigned coupons: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown'
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching coupons. Please try again.',
                'coupons' => [],
                'count' => 0,
                'error_details' => config('app.debug') ? $e->getMessage() : null,
            ], 200); // Return 200 to avoid logout, with empty coupons array
        }
    }
}

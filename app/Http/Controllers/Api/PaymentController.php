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
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('expiry_date', '>=', now())
            ->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired coupon code',
            ], 200);
        }

        $plan = Plan::find($request->plan_id);
        $discount = ($plan->price * $coupon->discount_percentage) / 100;
        $finalAmount = $plan->price - $discount;

        return response()->json([
            'valid' => true,
            'coupon' => [
                'code' => $coupon->code,
                'discount_percentage' => $coupon->discount_percentage,
                'expiry_date' => $coupon->expiry_date,
            ],
            'plan' => [
                'price' => $plan->price,
            ],
            'discount_amount' => number_format($discount, 2, '.', ''),
            'final_amount' => number_format($finalAmount, 2, '.', ''),
        ], 200);
    }

    /**
     * Create Razorpay order for plan upgrade
     */
    public function createRazorpayOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $plan = Plan::find($request->plan_id);

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

        DB::beginTransaction();
        try {
            // Initialize Razorpay API
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            // Convert amount to paise (Razorpay requires amount in smallest currency unit)
            $amountInPaise = $plan->price * 100;

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
                ]
            ]);

            // Store order in database
            $order = PlanOrder::create([
                'employee_id' => $userType === 'Employee' ? $user->id : null,
                'employer_id' => $userType === 'Employer' ? $user->id : null,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $plan->price,
                'currency' => 'INR',
                'status' => 'created',
                'notes' => 'Plan upgrade order',
            ]);

            DB::commit();

            return response()->json([
                'order_id' => $order->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'amount' => $plan->price,
                'currency' => 'INR',
                'razorpay_key' => config('services.razorpay.key'),
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
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
}

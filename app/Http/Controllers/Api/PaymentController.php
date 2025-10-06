<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\CommissionTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
}

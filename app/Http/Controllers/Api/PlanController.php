<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Get all plans (public or admin)
     */
    public function getAllPlans(Request $request)
    {
        $query = Plan::with('features');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $plans = $query->get();

        return response()->json(['plans' => $plans], 200);
    }

    /**
     * Get single plan
     */
    public function getPlan(Request $request, $id)
    {
        $plan = Plan::with('features')->find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        return response()->json(['plan' => $plan], 200);
    }

    /**
     * Create plan (Admin: Super Admin / Plan Upgrade Manager)
     */
    public function createPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:employee,employer',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
            'is_default' => 'nullable|boolean',
            // Employee plan features
            'jobs_can_apply' => 'nullable|integer|min:-1',
            'contact_details_can_view' => 'nullable|integer|min:-1',
            'whatsapp_alerts' => 'nullable|boolean',
            'sms_alerts' => 'nullable|boolean',
            'employer_can_view_contact_free' => 'nullable|boolean',
            // Employer plan features
            'jobs_can_post' => 'nullable|integer|min:-1',
            'employee_contact_details_can_view' => 'nullable|integer|min:-1',
            'features' => 'nullable|array',
            'features.*.feature_name' => 'required|string',
            'features.*.feature_value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If this plan is set as default, unset other default plans of the same type
        if ($request->is_default) {
            Plan::where('type', $request->type)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $planData = [
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'price' => $request->price,
            'validity_days' => $request->validity_days,
            'is_default' => $request->is_default ?? false,
        ];

        // Add employee-specific features
        if ($request->type === 'employee') {
            $planData['jobs_can_apply'] = $request->jobs_can_apply ?? 5;
            $planData['contact_details_can_view'] = $request->contact_details_can_view ?? 3;
            $planData['whatsapp_alerts'] = $request->whatsapp_alerts ?? false;
            $planData['sms_alerts'] = $request->sms_alerts ?? false;
            $planData['employer_can_view_contact_free'] = $request->employer_can_view_contact_free ?? false;
        }

        // Add employer-specific features
        if ($request->type === 'employer') {
            $planData['jobs_can_post'] = $request->jobs_can_post ?? 5;
            $planData['employee_contact_details_can_view'] = $request->employee_contact_details_can_view ?? 10;
        }

        $plan = Plan::create($planData);

        // Create plan features
        if ($request->has('features')) {
            foreach ($request->features as $feature) {
                PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_name' => $feature['feature_name'],
                    'feature_value' => $feature['feature_value'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Plan created',
            'plan' => $plan->load('features'),
        ], 201);
    }

    /**
     * Update plan (Admin: Super Admin / Plan Upgrade Manager)
     */
    public function updatePlan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:employee,employer',
            'price' => 'sometimes|required|numeric|min:0',
            'validity_days' => 'sometimes|required|integer|min:1',
            'is_default' => 'nullable|boolean',
            // Employee plan features
            'jobs_can_apply' => 'nullable|integer|min:-1',
            'contact_details_can_view' => 'nullable|integer|min:-1',
            'whatsapp_alerts' => 'nullable|boolean',
            'sms_alerts' => 'nullable|boolean',
            'employer_can_view_contact_free' => 'nullable|boolean',
            // Employer plan features
            'jobs_can_post' => 'nullable|integer|min:-1',
            'employee_contact_details_can_view' => 'nullable|integer|min:-1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        // If this plan is being set as default, unset other default plans of the same type
        if ($request->has('is_default') && $request->is_default) {
            Plan::where('type', $plan->type)
                ->where('id', '!=', $plan->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $plan->update($request->only([
            'name',
            'description',
            'type',
            'price',
            'validity_days',
            'is_default',
            // Employee plan features
            'jobs_can_apply',
            'contact_details_can_view',
            'whatsapp_alerts',
            'sms_alerts',
            'employer_can_view_contact_free',
            // Employer plan features
            'jobs_can_post',
            'employee_contact_details_can_view'
        ]));

        return response()->json([
            'message' => 'Plan updated',
            'plan' => $plan,
        ], 200);
    }

    /**
     * Delete plan (Admin: Super Admin)
     */
    public function deletePlan(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan deleted'], 200);
    }

    /**
     * Add feature to plan (Admin)
     */
    public function addPlanFeature(Request $request, $planId)
    {
        $validator = Validator::make($request->all(), [
            'feature_name' => 'required|string',
            'feature_value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::find($planId);

        if (!$plan) {
            return response()->json(['message' => 'Plan not found'], 404);
        }

        $feature = PlanFeature::create([
            'plan_id' => $planId,
            'feature_name' => $request->feature_name,
            'feature_value' => $request->feature_value,
        ]);

        return response()->json([
            'message' => 'Feature added',
            'feature' => $feature,
        ], 201);
    }

    /**
     * Remove feature from plan (Admin)
     */
    public function removePlanFeature(Request $request, $featureId)
    {
        $feature = PlanFeature::find($featureId);

        if (!$feature) {
            return response()->json(['message' => 'Feature not found'], 404);
        }

        $feature->delete();

        return response()->json(['message' => 'Feature removed'], 200);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanFeature;

class DefaultPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default free plan for employees
        $freePlan = Plan::create([
            'name' => 'Free Plan',
            'description' => 'Basic features for job seekers to get started',
            'type' => 'employee',
            'price' => 0.00,
            'validity_days' => 365, // 1 year
            'is_default' => true,
        ]);

        // Add features to free plan
        PlanFeature::create([
            'plan_id' => $freePlan->id,
            'feature_name' => 'Job Applications',
            'feature_value' => '5 per month',
        ]);
        PlanFeature::create([
            'plan_id' => $freePlan->id,
            'feature_name' => 'Profile Views',
            'feature_value' => 'Basic visibility',
        ]);
        PlanFeature::create([
            'plan_id' => $freePlan->id,
            'feature_name' => 'CV Generation',
            'feature_value' => '1 basic template',
        ]);

        // Create premium plan for employees
        $premiumPlan = Plan::create([
            'name' => 'Premium Plan',
            'description' => 'Enhanced features for serious job seekers',
            'type' => 'employee',
            'price' => 29.99,
            'validity_days' => 30, // 1 month
            'is_default' => false,
        ]);

        // Add features to premium plan
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Job Applications',
            'feature_value' => 'Unlimited',
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Profile Views',
            'feature_value' => 'High visibility',
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'CV Generation',
            'feature_value' => 'All premium templates',
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Professional CV Service',
            'feature_value' => 'Included',
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Priority Support',
            'feature_value' => 'Yes',
        ]);

        // Create professional plan for employees
        $professionalPlan = Plan::create([
            'name' => 'Professional Plan',
            'description' => 'Ultimate package for career advancement',
            'type' => 'employee',
            'price' => 79.99,
            'validity_days' => 90, // 3 months
            'is_default' => false,
        ]);

        // Add features to professional plan
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Job Applications',
            'feature_value' => 'Unlimited',
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Profile Views',
            'feature_value' => 'Premium visibility with featured listing',
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'CV Generation',
            'feature_value' => 'All templates + custom designs',
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Professional CV Service',
            'feature_value' => '3 revisions included',
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Career Counseling',
            'feature_value' => '2 sessions',
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Priority Support',
            'feature_value' => '24/7 dedicated support',
        ]);
    }
}

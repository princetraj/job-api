<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanFeature;

class DefaultEmployeePlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing employee plans
        Plan::where('type', 'employee')->delete();

        // Free Plan (Default)
        $freePlan = Plan::create([
            'name' => 'Free Plan',
            'description' => 'Basic plan for job seekers to get started',
            'type' => 'employee',
            'price' => 0.00,
            'validity_days' => 365,
            'is_default' => true,
            'jobs_can_apply' => 5,
            'contact_details_can_view' => 2,
            'whatsapp_alerts' => false,
            'sms_alerts' => false,
        ]);

        // Add features for Free Plan
        PlanFeature::create([
            'plan_id' => $freePlan->id,
            'feature_name' => 'Profile Creation',
            'feature_value' => 'Basic profile with resume upload'
        ]);
        PlanFeature::create([
            'plan_id' => $freePlan->id,
            'feature_name' => 'Job Search',
            'feature_value' => 'Search and browse all jobs'
        ]);

        // Basic Plan
        $basicPlan = Plan::create([
            'name' => 'Basic Plan',
            'description' => 'Enhanced features for active job seekers',
            'type' => 'employee',
            'price' => 9.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_apply' => 20,
            'contact_details_can_view' => 10,
            'whatsapp_alerts' => false,
            'sms_alerts' => true,
        ]);

        // Add features for Basic Plan
        PlanFeature::create([
            'plan_id' => $basicPlan->id,
            'feature_name' => 'Profile Creation',
            'feature_value' => 'Enhanced profile with multiple documents'
        ]);
        PlanFeature::create([
            'plan_id' => $basicPlan->id,
            'feature_name' => 'Priority Support',
            'feature_value' => 'Email support within 48 hours'
        ]);
        PlanFeature::create([
            'plan_id' => $basicPlan->id,
            'feature_name' => 'Application Tracking',
            'feature_value' => 'Track status of your applications'
        ]);

        // Professional Plan
        $proPlan = Plan::create([
            'name' => 'Professional Plan',
            'description' => 'Professional features with WhatsApp & SMS alerts',
            'type' => 'employee',
            'price' => 24.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_apply' => 50,
            'contact_details_can_view' => 30,
            'whatsapp_alerts' => true,
            'sms_alerts' => true,
        ]);

        // Add features for Professional Plan
        PlanFeature::create([
            'plan_id' => $proPlan->id,
            'feature_name' => 'Profile Creation',
            'feature_value' => 'Premium profile with portfolio showcase'
        ]);
        PlanFeature::create([
            'plan_id' => $proPlan->id,
            'feature_name' => 'Priority Support',
            'feature_value' => 'Priority email & chat support'
        ]);
        PlanFeature::create([
            'plan_id' => $proPlan->id,
            'feature_name' => 'Job Recommendations',
            'feature_value' => 'AI-powered job recommendations'
        ]);
        PlanFeature::create([
            'plan_id' => $proPlan->id,
            'feature_name' => 'Profile Visibility',
            'feature_value' => 'Higher visibility to employers'
        ]);

        // Premium Plan (Unlimited)
        $premiumPlan = Plan::create([
            'name' => 'Premium Plan',
            'description' => 'Unlimited access with all premium features',
            'type' => 'employee',
            'price' => 49.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_apply' => -1, // Unlimited
            'contact_details_can_view' => -1, // Unlimited
            'whatsapp_alerts' => true,
            'sms_alerts' => true,
        ]);

        // Add features for Premium Plan
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Profile Creation',
            'feature_value' => 'Elite profile with video introduction'
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Priority Support',
            'feature_value' => '24/7 dedicated support'
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Job Recommendations',
            'feature_value' => 'Advanced AI job matching'
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Profile Visibility',
            'feature_value' => 'Featured profile - top of search results'
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Resume Review',
            'feature_value' => 'Professional resume review service'
        ]);
        PlanFeature::create([
            'plan_id' => $premiumPlan->id,
            'feature_name' => 'Interview Preparation',
            'feature_value' => 'Access to interview preparation resources'
        ]);

        $this->command->info('Default employee plans created successfully!');
        $this->command->info('- Free Plan (Default): 5 job applications, 2 contact views');
        $this->command->info('- Basic Plan ($9.99): 20 job applications, 10 contact views, SMS alerts');
        $this->command->info('- Professional Plan ($24.99): 50 job applications, 30 contact views, WhatsApp & SMS alerts');
        $this->command->info('- Premium Plan ($49.99): Unlimited applications & contact views, all alerts');
    }
}

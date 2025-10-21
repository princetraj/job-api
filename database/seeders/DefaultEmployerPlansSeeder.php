<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanFeature;

class DefaultEmployerPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing employer plans
        Plan::where('type', 'employer')->delete();

        // Starter Plan (Default)
        $starterPlan = Plan::create([
            'name' => 'Starter Plan',
            'description' => 'Basic plan for small businesses to start hiring',
            'type' => 'employer',
            'price' => 0.00,
            'validity_days' => 30,
            'is_default' => true,
            'jobs_can_post' => 3,
            'employee_contact_details_can_view' => 10,
        ]);

        // Add features for Starter Plan
        PlanFeature::create([
            'plan_id' => $starterPlan->id,
            'feature_name' => 'Job Posting',
            'feature_value' => 'Basic job listing with company details'
        ]);
        PlanFeature::create([
            'plan_id' => $starterPlan->id,
            'feature_name' => 'Applicant Management',
            'feature_value' => 'View and manage job applications'
        ]);

        // Business Plan
        $businessPlan = Plan::create([
            'name' => 'Business Plan',
            'description' => 'Enhanced features for growing businesses',
            'type' => 'employer',
            'price' => 49.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_post' => 10,
            'employee_contact_details_can_view' => 50,
        ]);

        // Add features for Business Plan
        PlanFeature::create([
            'plan_id' => $businessPlan->id,
            'feature_name' => 'Job Posting',
            'feature_value' => 'Featured job listings with priority placement'
        ]);
        PlanFeature::create([
            'plan_id' => $businessPlan->id,
            'feature_name' => 'Applicant Management',
            'feature_value' => 'Advanced filtering and sorting of candidates'
        ]);
        PlanFeature::create([
            'plan_id' => $businessPlan->id,
            'feature_name' => 'Analytics',
            'feature_value' => 'Basic job posting analytics and insights'
        ]);
        PlanFeature::create([
            'plan_id' => $businessPlan->id,
            'feature_name' => 'Support',
            'feature_value' => 'Email support within 24 hours'
        ]);

        // Professional Plan
        $professionalPlan = Plan::create([
            'name' => 'Professional Plan',
            'description' => 'Professional hiring solution for established companies',
            'type' => 'employer',
            'price' => 99.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_post' => 25,
            'employee_contact_details_can_view' => 150,
        ]);

        // Add features for Professional Plan
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Job Posting',
            'feature_value' => 'Premium featured listings with top placement'
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Applicant Management',
            'feature_value' => 'Advanced ATS with custom workflows'
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Analytics',
            'feature_value' => 'Advanced analytics with detailed reports'
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Candidate Search',
            'feature_value' => 'AI-powered candidate matching'
        ]);
        PlanFeature::create([
            'plan_id' => $professionalPlan->id,
            'feature_name' => 'Support',
            'feature_value' => 'Priority support with dedicated account manager'
        ]);

        // Enterprise Plan (Unlimited)
        $enterprisePlan = Plan::create([
            'name' => 'Enterprise Plan',
            'description' => 'Unlimited hiring solution for large organizations',
            'type' => 'employer',
            'price' => 199.99,
            'validity_days' => 30,
            'is_default' => false,
            'jobs_can_post' => -1, // Unlimited
            'employee_contact_details_can_view' => -1, // Unlimited
        ]);

        // Add features for Enterprise Plan
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Job Posting',
            'feature_value' => 'Unlimited premium featured listings'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Applicant Management',
            'feature_value' => 'Enterprise ATS with API integration'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Analytics',
            'feature_value' => 'Custom analytics dashboard with export'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Candidate Search',
            'feature_value' => 'Advanced AI matching with talent pool access'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Branding',
            'feature_value' => 'Custom company page and employer branding'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Support',
            'feature_value' => '24/7 premium support with dedicated team'
        ]);
        PlanFeature::create([
            'plan_id' => $enterprisePlan->id,
            'feature_name' => 'Multi-user Access',
            'feature_value' => 'Unlimited team members with role permissions'
        ]);

        $this->command->info('Default employer plans created successfully!');
        $this->command->info('- Starter Plan (Default/Free): 3 job posts, 10 employee contact views');
        $this->command->info('- Business Plan ($49.99): 10 job posts, 50 employee contact views');
        $this->command->info('- Professional Plan ($99.99): 25 job posts, 150 employee contact views');
        $this->command->info('- Enterprise Plan ($199.99): Unlimited job posts & employee contact views');
    }
}

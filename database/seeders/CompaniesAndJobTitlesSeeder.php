<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\JobTitle;

class CompaniesAndJobTitlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample Companies
        $companies = [
            'Google',
            'Microsoft',
            'Amazon',
            'Apple',
            'Meta',
            'Netflix',
            'Tesla',
            'IBM',
            'Oracle',
            'Adobe',
            'Salesforce',
            'Intel',
            'NVIDIA',
            'AMD',
            'Cisco',
            'Samsung',
            'Sony',
            'Dell',
            'HP',
            'Lenovo'
        ];

        foreach ($companies as $companyName) {
            Company::create([
                'name' => $companyName,
                'approval_status' => 'approved',
                'created_by_type' => 'admin',
            ]);
        }

        // Sample Job Titles
        $jobTitles = [
            'Software Engineer',
            'Senior Software Engineer',
            'Staff Software Engineer',
            'Principal Engineer',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'DevOps Engineer',
            'Data Scientist',
            'Data Analyst',
            'Machine Learning Engineer',
            'Product Manager',
            'Project Manager',
            'UI/UX Designer',
            'Graphic Designer',
            'Business Analyst',
            'Quality Assurance Engineer',
            'Security Engineer',
            'System Administrator',
            'Database Administrator',
            'Mobile Developer',
            'Cloud Architect',
            'Technical Lead',
            'Engineering Manager',
            'Director of Engineering'
        ];

        foreach ($jobTitles as $titleName) {
            JobTitle::create([
                'name' => $titleName,
                'approval_status' => 'approved',
                'created_by_type' => 'admin',
            ]);
        }

        $this->command->info('Companies and Job Titles seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Degree;
use App\Models\University;
use App\Models\FieldOfStudy;

class EducationCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample Degrees
        $degrees = [
            'Bachelor of Science',
            'Bachelor of Arts',
            'Bachelor of Engineering',
            'Bachelor of Commerce',
            'Master of Science',
            'Master of Arts',
            'Master of Business Administration',
            'Master of Engineering',
            'Doctor of Philosophy',
            'Associate Degree',
            'Diploma',
            'Certificate',
        ];

        foreach ($degrees as $degree) {
            Degree::firstOrCreate(
                ['name' => $degree],
                [
                    'approval_status' => 'approved',
                    'created_by_type' => 'admin',
                ]
            );
        }

        // Sample Universities
        $universities = [
            'Harvard University',
            'Stanford University',
            'Massachusetts Institute of Technology',
            'University of California, Berkeley',
            'Oxford University',
            'Cambridge University',
            'Yale University',
            'Princeton University',
            'Columbia University',
            'University of Chicago',
            'Imperial College London',
            'ETH Zurich',
            'University of Toronto',
            'National University of Singapore',
            'University of Melbourne',
            'Indian Institute of Technology',
            'Tsinghua University',
            'Peking University',
            'University of Tokyo',
            'Seoul National University',
        ];

        foreach ($universities as $university) {
            University::firstOrCreate(
                ['name' => $university],
                [
                    'approval_status' => 'approved',
                    'created_by_type' => 'admin',
                ]
            );
        }

        // Sample Fields of Study
        $fields = [
            'Computer Science',
            'Information Technology',
            'Software Engineering',
            'Data Science',
            'Artificial Intelligence',
            'Mechanical Engineering',
            'Electrical Engineering',
            'Civil Engineering',
            'Chemical Engineering',
            'Business Administration',
            'Finance',
            'Accounting',
            'Marketing',
            'Economics',
            'Psychology',
            'Biology',
            'Chemistry',
            'Physics',
            'Mathematics',
            'English Literature',
            'History',
            'Political Science',
            'Sociology',
            'Law',
            'Medicine',
            'Nursing',
            'Pharmacy',
            'Architecture',
            'Fine Arts',
            'Graphic Design',
        ];

        foreach ($fields as $field) {
            FieldOfStudy::firstOrCreate(
                ['name' => $field],
                [
                    'approval_status' => 'approved',
                    'created_by_type' => 'admin',
                ]
            );
        }

        $this->command->info('Education catalog seeded successfully!');
    }
}

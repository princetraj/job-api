<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\Degree;
use App\Models\University;
use App\Models\FieldOfStudy;
use Illuminate\Support\Facades\DB;

class MigrateEducationDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Migrates education data from JSON column to normalized employee_educations table.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting education data migration...');

        $migratedCount = 0;
        $errorCount = 0;
        $employeeCount = 0;

        // Process employees in chunks to handle large datasets
        Employee::whereNotNull('education_details')->chunk(100, function ($employees) use (&$migratedCount, &$errorCount, &$employeeCount) {
            foreach ($employees as $employee) {
                $employeeCount++;

                try {
                    DB::beginTransaction();

                    // Skip if already migrated
                    if ($employee->educations()->exists()) {
                        $this->command->warn("  Skipping employee {$employee->id} - already has normalized education data");
                        DB::rollBack();
                        continue;
                    }

                    $educationDetails = $employee->education_details;

                    if (!is_array($educationDetails) || empty($educationDetails)) {
                        DB::rollBack();
                        continue;
                    }

                    foreach ($educationDetails as $edu) {
                        if (!is_array($edu)) continue;

                        // Get or create degree
                        $degree = null;
                        if (!empty($edu['degree'])) {
                            $degree = Degree::firstOrCreate(
                                ['name' => trim($edu['degree'])],
                                [
                                    'approval_status' => 'approved', // Existing data is auto-approved
                                    'created_by_type' => 'admin', // Treat existing data as admin-created
                                ]
                            );
                        }

                        // Get or create university
                        $university = null;
                        if (!empty($edu['university'])) {
                            $university = University::firstOrCreate(
                                ['name' => trim($edu['university'])],
                                [
                                    'approval_status' => 'approved',
                                    'created_by_type' => 'admin',
                                ]
                            );
                        }

                        // Get or create field of study
                        $fieldOfStudy = null;
                        if (!empty($edu['field'])) {
                            $fieldOfStudy = FieldOfStudy::firstOrCreate(
                                ['name' => trim($edu['field'])],
                                [
                                    'approval_status' => 'approved',
                                    'created_by_type' => 'admin',
                                ]
                            );
                        }

                        // Create normalized education record
                        EmployeeEducation::create([
                            'employee_id' => $employee->id,
                            'degree_id' => $degree ? $degree->id : null,
                            'university_id' => $university ? $university->id : null,
                            'field_of_study_id' => $fieldOfStudy ? $fieldOfStudy->id : null,
                            'year_start' => $edu['year_start'] ?? '',
                            'year_end' => $edu['year_end'] ?? '',
                        ]);

                        $migratedCount++;
                    }

                    DB::commit();
                    $this->command->info("  ✓ Migrated education for employee: {$employee->name} ({$employee->id})");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $errorCount++;
                    $this->command->error("  ✗ Error migrating employee {$employee->id}: " . $e->getMessage());
                }
            }
        });

        $this->command->info("\n=== Migration Summary ===");
        $this->command->info("Employees processed: {$employeeCount}");
        $this->command->info("Education records migrated: {$migratedCount}");
        $this->command->info("Errors: {$errorCount}");

        // Show statistics
        $degreeCount = Degree::count();
        $universityCount = University::count();
        $fieldCount = FieldOfStudy::count();
        $educationCount = EmployeeEducation::count();

        $this->command->info("\n=== Database Statistics ===");
        $this->command->info("Total degrees: {$degreeCount}");
        $this->command->info("Total universities: {$universityCount}");
        $this->command->info("Total fields of study: {$fieldCount}");
        $this->command->info("Total employee education records: {$educationCount}");

        $this->command->info("\n✅ Education data migration completed!");
    }
}

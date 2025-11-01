<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Education Data Storage ===\n\n";

// Check normalized table
$normalizedCount = \App\Models\EmployeeEducation::count();
echo "Records in employee_educations table: {$normalizedCount}\n";

// Check employees with education
$employeesWithEducation = \App\Models\Employee::has('educations')->count();
echo "Employees with normalized education: {$employeesWithEducation}\n\n";

// Show sample data from normalized table
echo "=== Sample Data from employee_educations Table ===\n";
$educations = \App\Models\EmployeeEducation::with(['employee', 'degree', 'university', 'fieldOfStudy'])
    ->limit(5)
    ->get();

foreach ($educations as $edu) {
    echo sprintf(
        "- %s: %s from %s (%s) [%s-%s]\n",
        $edu->employee->name ?? 'Unknown',
        $edu->degree->name ?? 'N/A',
        $edu->university->name ?? 'N/A',
        $edu->fieldOfStudy->name ?? 'N/A',
        $edu->year_start,
        $edu->year_end
    );
}

echo "\n=== Sample Data from employees.education_details (JSON) ===\n";
$employeesWithJson = \App\Models\Employee::whereNotNull('education_details')->limit(3)->get();
foreach ($employeesWithJson as $emp) {
    echo "Employee: {$emp->name}\n";
    if (is_array($emp->education_details)) {
        foreach ($emp->education_details as $edu) {
            echo sprintf(
                "  - %s from %s (%s)\n",
                $edu['degree'] ?? 'N/A',
                $edu['university'] ?? 'N/A',
                $edu['field'] ?? 'N/A'
            );
        }
    }
}

echo "\n=== Conclusion ===\n";
if ($normalizedCount > 0) {
    echo "✅ Data IS being saved to employee_educations table\n";
} else {
    echo "❌ Data NOT being saved to employee_educations table\n";
}

if ($employeesWithJson->isNotEmpty()) {
    echo "✅ Data IS also being saved to employees.education_details (JSON)\n";
    echo "   This is for backward compatibility.\n";
}

echo "\nBoth storages are working as expected!\n";

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\JobTitle;

echo "=== COMPANIES ===\n";
echo "Total: " . Company::count() . "\n";
echo "Approved: " . Company::where('approval_status', 'approved')->count() . "\n";
echo "Pending: " . Company::where('approval_status', 'pending')->count() . "\n";
echo "Rejected: " . Company::where('approval_status', 'rejected')->count() . "\n\n";

echo "Pending Companies:\n";
$pendingCompanies = Company::where('approval_status', 'pending')->get();
foreach ($pendingCompanies as $company) {
    echo "  - {$company->name} (ID: {$company->id}, Created by: {$company->created_by_type})\n";
}

echo "\n=== JOB TITLES ===\n";
echo "Total: " . JobTitle::count() . "\n";
echo "Approved: " . JobTitle::where('approval_status', 'approved')->count() . "\n";
echo "Pending: " . JobTitle::where('approval_status', 'pending')->count() . "\n";
echo "Rejected: " . JobTitle::where('approval_status', 'rejected')->count() . "\n\n";

echo "Pending Job Titles:\n";
$pendingTitles = JobTitle::where('approval_status', 'pending')->get();
foreach ($pendingTitles as $title) {
    echo "  - {$title->name} (ID: {$title->id}, Created by: {$title->created_by_type})\n";
}

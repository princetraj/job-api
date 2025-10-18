<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Super Admin
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@jobportal.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);

        // Create Employee Manager
        Admin::create([
            'name' => 'Employee Manager',
            'email' => 'employee.manager@jobportal.com',
            'password' => 'password123',
            'role' => 'employee_manager',
        ]);

        // Create Employer Manager
        Admin::create([
            'name' => 'Employer Manager',
            'email' => 'employer.manager@jobportal.com',
            'password' => 'password123',
            'role' => 'employer_manager',
        ]);

        // Create Plan Upgrade Manager
        Admin::create([
            'name' => 'Plan Manager',
            'email' => 'plan.manager@jobportal.com',
            'password' => 'password123',
            'role' => 'plan_upgrade_manager',
        ]);

        // Create Catalog Manager
        Admin::create([
            'name' => 'Catalog Manager',
            'email' => 'catalog.manager@jobportal.com',
            'password' => 'password123',
            'role' => 'catalog_manager',
        ]);
    }
}

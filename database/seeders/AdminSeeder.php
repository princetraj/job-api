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

        // Create Manager
        Admin::create([
            'name' => 'Manager',
            'email' => 'manager@jobportal.com',
            'password' => 'password123',
            'role' => 'manager',
        ]);

        // Create Staff
        Admin::create([
            'name' => 'Staff',
            'email' => 'staff@jobportal.com',
            'password' => 'password123',
            'role' => 'staff',
        ]);
    }
}

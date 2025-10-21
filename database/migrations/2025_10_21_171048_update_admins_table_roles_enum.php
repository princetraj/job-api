<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAdminsTableRolesEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, update existing records to map old roles to new roles
        // Map all manager-type roles to 'manager'
        \DB::table('admins')
            ->whereIn('role', ['employee_manager', 'employer_manager', 'plan_upgrade_manager', 'catalog_manager'])
            ->update(['role' => 'manager']);

        // Map sales_staff to 'staff' if any exist
        \DB::table('admins')
            ->where('role', 'sales_staff')
            ->update(['role' => 'staff']);

        // Now modify the enum column to only allow new values
        \DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'manager', 'staff') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to old enum values
        \DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'employee_manager', 'employer_manager', 'plan_upgrade_manager', 'catalog_manager', 'sales_staff') NOT NULL");

        // Note: We cannot accurately reverse the role updates since we don't know
        // which specific manager type each admin originally had
    }
}

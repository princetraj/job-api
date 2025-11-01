<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropEducationDetailsColumnFromEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the education_details JSON column
            // All education data is now stored in the normalized employee_educations table
            $table->dropColumn('education_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Restore the education_details column for rollback
            $table->json('education_details')->nullable()->after('address');
        });
    }
}

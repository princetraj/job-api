<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployerPlanFeaturesToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            // Employer plan features
            $table->integer('jobs_can_post')->default(5)->comment('Number of jobs employer can post. -1 for unlimited');
            $table->integer('employee_contact_details_can_view')->default(10)->comment('Number of employee contact details can view. -1 for unlimited');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'jobs_can_post',
                'employee_contact_details_can_view'
            ]);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobsRemainingToEmployeePlanSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_plan_subscriptions', function (Blueprint $table) {
            $table->integer('jobs_remaining')->nullable()->after('is_default')->comment('Number of job applications remaining. -1 for unlimited');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn('jobs_remaining');
        });
    }
}

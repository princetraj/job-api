<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactViewsRemainingToEmployeePlanSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_plan_subscriptions', function (Blueprint $table) {
            $table->integer('contact_views_remaining')->nullable()->after('jobs_remaining')->comment('Number of employer contact views remaining. -1 for unlimited');
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
            $table->dropColumn('contact_views_remaining');
        });
    }
}

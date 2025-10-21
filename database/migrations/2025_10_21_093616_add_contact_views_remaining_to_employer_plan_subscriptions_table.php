<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactViewsRemainingToEmployerPlanSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employer_plan_subscriptions', function (Blueprint $table) {
            $table->integer('contact_views_remaining')->nullable()->comment('Number of employee contact details views remaining. -1 for unlimited');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employer_plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn('contact_views_remaining');
        });
    }
}

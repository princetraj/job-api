<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PopulateContactViewsRemainingForExistingSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update all existing employer_plan_subscriptions where contact_views_remaining is null
        // Set it to the plan's employee_contact_details_can_view value
        DB::statement('
            UPDATE employer_plan_subscriptions eps
            INNER JOIN plans p ON eps.plan_id = p.id
            SET eps.contact_views_remaining = p.employee_contact_details_can_view
            WHERE eps.contact_views_remaining IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No need to reverse this data migration
        // The field will remain populated
    }
}

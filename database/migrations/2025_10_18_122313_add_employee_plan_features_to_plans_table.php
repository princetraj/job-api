<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeePlanFeaturesToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            // Employee plan features
            $table->integer('jobs_can_apply')->default(5)->comment('Number of jobs employee can apply. -1 for unlimited');
            $table->integer('contact_details_can_view')->default(3)->comment('Number of employer contact details can view. -1 for unlimited');
            $table->boolean('whatsapp_alerts')->default(false)->comment('Enable WhatsApp alert notifications');
            $table->boolean('sms_alerts')->default(false)->comment('Enable SMS/text alert notifications');
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
                'jobs_can_apply',
                'contact_details_can_view',
                'whatsapp_alerts',
                'sms_alerts'
            ]);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountStatusToEmployeesAndEmployersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('account_status', ['pending', 'approved', 'rejected'])->default('pending')->after('email');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->enum('account_status', ['pending', 'approved', 'rejected'])->default('pending')->after('email');
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
            $table->dropColumn('account_status');
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });
    }
}

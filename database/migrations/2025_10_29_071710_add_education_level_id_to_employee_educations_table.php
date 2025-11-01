<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEducationLevelIdToEmployeeEducationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_educations', function (Blueprint $table) {
            // Add education level foreign key
            $table->foreignId('education_level_id')->nullable()->after('employee_id')->constrained('education_levels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_educations', function (Blueprint $table) {
            $table->dropForeign(['education_level_id']);
            $table->dropColumn('education_level_id');
        });
    }
}

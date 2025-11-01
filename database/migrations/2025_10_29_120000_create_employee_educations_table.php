<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeEducationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id');
            $table->foreignId('degree_id')->nullable()->constrained('degrees')->onDelete('set null');
            $table->foreignId('university_id')->nullable()->constrained('universities')->onDelete('set null');
            $table->foreignId('field_of_study_id')->nullable()->constrained('field_of_studies')->onDelete('set null');
            $table->string('year_start', 4);
            $table->string('year_end', 4);
            $table->timestamps();

            // Foreign key to employees table
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            // Add indexes for better query performance
            $table->index('employee_id');
            $table->index('degree_id');
            $table->index('university_id');
            $table->index('field_of_study_id');
            $table->index(['employee_id', 'degree_id']);
            $table->index(['employee_id', 'university_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_educations');
    }
}

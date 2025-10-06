<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 191)->unique();
            $table->string('mobile', 20)->unique();
            $table->string('password_hash', 191);
            $table->string('name', 191);
            $table->enum('gender', ['M', 'F', 'O']);
            $table->date('dob')->nullable();
            $table->json('address')->nullable();
            $table->json('education_details')->nullable();
            $table->json('experience_details')->nullable();
            $table->json('skills_details')->nullable();
            $table->string('cv_url', 191)->nullable();
            $table->uuid('plan_id')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}

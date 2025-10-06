<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employer_id');
            $table->string('title', 191);
            $table->text('description');
            $table->string('salary', 191)->nullable();
            $table->uuid('location_id')->nullable();
            $table->uuid('category_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_end_date')->nullable();
            $table->timestamps();

            $table->foreign('employer_id')->references('id')->on('employers')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('job_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}

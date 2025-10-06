<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name', 191);
            $table->string('email', 191)->unique();
            $table->string('contact', 20)->nullable();
            $table->json('address')->nullable();
            $table->uuid('industry_type')->nullable();
            $table->string('password_hash', 191);
            $table->uuid('plan_id')->nullable();
            $table->timestamps();

            $table->foreign('industry_type')->references('id')->on('industries')->onDelete('set null');
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
        Schema::dropIfExists('employers');
    }
}

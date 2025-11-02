<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->uuid('created_by')->nullable();
            $table->string('created_by_type')->nullable(); // 'admin' or 'employee'
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('approval_status');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}

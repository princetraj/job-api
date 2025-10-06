<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id');
            $table->uuid('payment_id')->nullable();
            $table->decimal('amount_earned', 10, 2);
            $table->enum('type', ['coupon_based', 'manual']);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_transactions');
    }
}

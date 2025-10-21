<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('razorpay_payment_id')->unique();
            $table->string('razorpay_order_id');
            $table->string('razorpay_signature');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status'); // success, failed
            $table->string('method')->nullable(); // card, netbanking, wallet, upi
            $table->text('error_description')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('plan_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
}

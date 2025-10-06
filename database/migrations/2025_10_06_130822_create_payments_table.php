<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('user_type'); // Changed from enum to string for polymorphic
            $table->uuid('plan_id');
            $table->uuid('coupon_id')->nullable();
            $table->decimal('amount', 10, 2); // Final amount after discount
            $table->decimal('original_amount', 10, 2)->nullable(); // Original price before discount
            $table->decimal('discount_amount', 10, 2)->default(0); // Renamed from 'discount'
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('transaction_id', 191)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

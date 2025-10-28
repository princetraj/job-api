<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToCommissionTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('commission_transactions', function (Blueprint $table) {
            // Add reference to plan_orders table
            $table->uuid('order_id')->nullable()->after('payment_id');

            // Add reference to coupons table
            $table->uuid('coupon_id')->nullable()->after('order_id');

            // Add transaction details
            $table->decimal('transaction_amount', 10, 2)->nullable()->after('amount_earned');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('transaction_amount');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_amount');

            // Add foreign keys
            $table->foreign('order_id')->references('id')->on('plan_orders')->onDelete('set null');
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
        Schema::table('commission_transactions', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['order_id']);
            $table->dropForeign(['coupon_id']);

            // Drop columns
            $table->dropColumn(['order_id', 'coupon_id', 'transaction_amount', 'discount_amount', 'discount_percentage']);
        });
    }
}

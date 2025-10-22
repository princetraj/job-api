<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupon_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('coupon_id');
            $table->uuid('user_id');
            $table->enum('user_type', ['employee', 'employer']);
            $table->uuid('assigned_by');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('admins')->onDelete('cascade');

            // Unique constraint to prevent duplicate assignments
            $table->unique(['coupon_id', 'user_id', 'user_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_users');
    }
}

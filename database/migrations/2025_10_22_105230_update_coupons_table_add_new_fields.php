<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCouponsTableAddNewFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Rename staff_id to created_by for clarity
            $table->renameColumn('staff_id', 'created_by');

            // Add new fields
            $table->string('name', 191)->after('code');
            $table->enum('coupon_for', ['employee', 'employer'])->after('name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('expiry_date');
            $table->uuid('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Add foreign key for approved_by
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['approved_by']);

            // Drop new columns
            $table->dropColumn(['name', 'coupon_for', 'status', 'approved_by', 'approved_at']);

            // Rename back
            $table->renameColumn('created_by', 'staff_id');
        });
    }
}

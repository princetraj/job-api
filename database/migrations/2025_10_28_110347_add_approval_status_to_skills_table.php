<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalStatusToSkillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->enum('approval_status', ['approved', 'pending', 'rejected'])->default('approved')->after('name');
            $table->uuid('created_by')->nullable()->after('approval_status');
            $table->string('created_by_type')->nullable()->after('created_by'); // 'admin' or 'employee'
            $table->text('rejection_reason')->nullable()->after('created_by_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'created_by', 'created_by_type', 'rejection_reason']);
        });
    }
}

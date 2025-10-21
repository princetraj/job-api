<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfilePhotoFieldsToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_photo_url', 191)->nullable()->after('cv_url');
            $table->enum('profile_photo_status', ['pending', 'approved', 'rejected'])->nullable()->after('profile_photo_url');
            $table->text('profile_photo_rejection_reason')->nullable()->after('profile_photo_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_url', 'profile_photo_status', 'profile_photo_rejection_reason']);
        });
    }
}

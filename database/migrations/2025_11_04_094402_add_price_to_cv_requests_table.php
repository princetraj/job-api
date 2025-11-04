<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceToCvRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cv_requests', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0.00)->after('status');
            $table->string('payment_status')->default('pending')->after('price'); // pending, paid, refunded
            $table->string('payment_transaction_id')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cv_requests', function (Blueprint $table) {
            $table->dropColumn(['price', 'payment_status', 'payment_transaction_id']);
        });
    }
}

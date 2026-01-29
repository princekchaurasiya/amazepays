<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMerchantOrderIdToQsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_orders') && !Schema::hasColumn('qs_orders', 'merchant_order_id')) {
            Schema::table('qs_orders', function (Blueprint $table) {
                $table->uuid('merchant_order_id')->nullable()->unique();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qs_orders', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUnlimitPaymentTableAddMissingFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unlimit_payment', function (Blueprint $table) {
             $table->integer('qty')->default(1)->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->string('sku')->nullable();
            $table->string('merchant_order_id')->nullable();
            $table->string('payment_mode')->nullable()->change();
            $table->string('order_status')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unlimit_payment', function (Blueprint $table) {
            //
        });
    }
}

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
        if (Schema::hasTable('unlimit_payment')) {
            Schema::table('unlimit_payment', function (Blueprint $table) {
                if (Schema::hasColumn('unlimit_payment', 'qty')) {
                    $table->integer('qty')->default(1)->change();
                }
                if (Schema::hasColumn('unlimit_payment', 'price')) {
                    $table->decimal('price', 10, 2)->nullable()->change();
                }
                if (!Schema::hasColumn('unlimit_payment', 'sku')) {
                    $table->string('sku')->nullable();
                }
                if (!Schema::hasColumn('unlimit_payment', 'merchant_order_id')) {
                    $table->string('merchant_order_id')->nullable();
                }
                if (Schema::hasColumn('unlimit_payment', 'payment_mode')) {
                    $table->string('payment_mode')->nullable()->change();
                }
                if (Schema::hasColumn('unlimit_payment', 'order_status')) {
                    $table->string('order_status')->nullable()->change();
                }
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
        Schema::table('unlimit_payment', function (Blueprint $table) {
            //
        });
    }
}

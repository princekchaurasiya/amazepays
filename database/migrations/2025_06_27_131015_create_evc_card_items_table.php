<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvcCardItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evc_card_items', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code');
            $table->string('product_name');
            $table->string('card_no');
            $table->string('card_pin');
            $table->string('card_status');
            $table->date('expiry_date');
            $table->decimal('balance_basic', 10, 2);
            $table->decimal('balance_bonus', 10, 2)->nullable();
            $table->decimal('balance_total', 10, 2);
            $table->decimal('bonus_given', 10, 2)->nullable();
            $table->string('deal_no');
            $table->string('receipt_no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evc_card_items');
    }
}

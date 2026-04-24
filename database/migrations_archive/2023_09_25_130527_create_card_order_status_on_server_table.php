<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardOrderStatusOnServerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_order_status_on_server', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('statusLabel');
            $table->string('orderId');
            $table->string('refno');
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
        Schema::dropIfExists('card_order_status_on_server');
    }
}

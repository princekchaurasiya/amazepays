<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id');
            $table->timestamp('request_time');
            $table->string('amount');
            $table->string('currency');
            $table->timestamp('expire_at')->nullable();
            $table->uuid('merchant_order_id');
            $table->json('items');
            $table->string('customer_email');
            $table->string('payment_method');
            $table->json('api_response')->nullable(); // store response from Unlimit
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
        Schema::dropIfExists('invoices');
    }
}

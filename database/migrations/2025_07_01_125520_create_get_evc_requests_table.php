<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGetEvcRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('get_evc_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('distributor_id');
            $table->string('sku_code');
            $table->integer('no_of_card');
            $table->decimal('amount', 10, 2);
            $table->string('receipt_no');
            $table->string('req_id');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->string('mobile_no');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('pincode');
            $table->string('curr');
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
        Schema::dropIfExists('get_evc_requests');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method')->nullable();
            $table->uuid('merchant_order_id')->nullable();
            $table->string('merchant_order_description')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency')->nullable();
            $table->timestamp('created_at_api')->nullable();
            $table->string('decline_reason')->nullable();
            $table->string('decline_code')->nullable();
            $table->boolean('is_3d')->nullable();
            $table->string('arn')->nullable();
            $table->string('rrn')->nullable();
            $table->decimal('original_amount', 10, 2)->nullable();
            $table->string('masked_pan')->nullable();
            $table->string('holder')->nullable();
            $table->string('issuing_country_code')->nullable();
            $table->string('customer_email')->nullable();
            $table->ipAddress('customer_ip')->nullable();
            $table->string('customer_locale')->nullable();
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
        Schema::dropIfExists('payments');
    }
}

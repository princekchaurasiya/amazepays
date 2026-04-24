<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnlimitPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('unlimit_payment')) {
            Schema::create('unlimit_payment', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('merchant_order_id')->nullable();
                $table->string('tracking_id')->nullable();
                $table->string('bank_ref_no')->nullable();
                $table->string('payment_status')->nullable();
                $table->string('order_status')->nullable();
                $table->string('failure_message')->nullable();
                $table->string('payment_mode')->nullable();
                $table->string('card_name')->nullable();
                $table->string('status_code')->nullable();
                $table->text('status_message')->nullable();
                $table->string('currency')->nullable()->default('INR');
                $table->decimal('amount', 10, 2)->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->integer('qty')->default(1)->nullable();
                $table->string('sku')->nullable();

                // Billing fields
                $table->string('billing_name')->nullable();
                $table->string('billing_address')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_state')->nullable();
                $table->string('billing_zip')->nullable();
                $table->string('billing_country')->nullable();
                $table->string('billing_tel')->nullable();
                $table->string('billing_email')->nullable();
                $table->text('billing_notes')->nullable();

                // Delivery fields
                $table->string('delivery_name')->nullable();
                $table->string('delivery_address')->nullable();
                $table->string('delivery_city')->nullable();
                $table->string('delivery_state')->nullable();
                $table->string('delivery_zip')->nullable();
                $table->string('delivery_country')->nullable();
                $table->string('delivery_tel')->nullable();

                // Additional fields
                $table->string('merchant_param1')->nullable();
                $table->string('merchant_param2')->nullable();
                $table->string('merchant_param3')->nullable();
                $table->string('merchant_param4')->nullable();
                $table->string('merchant_param5')->nullable();
                $table->string('vault')->nullable();
                $table->string('offer_type')->nullable();
                $table->string('offer_code')->nullable();
                $table->decimal('discount_value', 10, 2)->nullable();
                $table->decimal('mer_amount', 10, 2)->nullable();
                $table->string('eci_value')->nullable();
                $table->integer('retry')->nullable();
                $table->string('response_code')->nullable();
                $table->dateTime('trans_date')->nullable();
                $table->string('bin_country')->nullable();

                // Response fields
                $table->longText('unlimit_response')->nullable();
                $table->longText('raw_callback')->nullable();

                $table->timestamps();

                // Indexes
                $table->index('user_id');
                $table->index('order_id');
                $table->index('merchant_order_id');
                $table->index('tracking_id');
                $table->index('payment_status');
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
        Schema::dropIfExists('unlimit_payment');
    }
}

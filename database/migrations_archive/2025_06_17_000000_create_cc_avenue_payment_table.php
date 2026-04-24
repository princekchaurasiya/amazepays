<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CC Avenue callback storage. order_id is not constrained here because the
 * `orders` table is created later in the migration timeline; resolve FK in a follow-up if needed.
 */
class CreateCcAvenuePaymentTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('cc_avenue_payment')) {
            Schema::create('cc_avenue_payment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('tracking_id')->nullable();
                $table->string('bank_ref_no')->nullable();
                $table->string('order_status')->nullable();
                $table->string('failure_message')->nullable();
                $table->string('payment_mode')->nullable();
                $table->string('card_name')->nullable();
                $table->string('status_code')->nullable();
                $table->text('status_message')->nullable();
                $table->string('currency')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('billing_name')->nullable();
                $table->string('billing_address')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_state')->nullable();
                $table->string('billing_zip')->nullable();
                $table->string('billing_country')->nullable();
                $table->string('billing_tel')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('delivery_name')->nullable();
                $table->string('delivery_address')->nullable();
                $table->string('delivery_city')->nullable();
                $table->string('delivery_state')->nullable();
                $table->string('delivery_zip')->nullable();
                $table->string('delivery_country')->nullable();
                $table->string('delivery_tel')->nullable();
                $table->string('merchant_param1')->nullable();
                $table->string('merchant_param2')->nullable();
                $table->string('merchant_param3')->nullable();
                $table->string('merchant_param4')->nullable();
                $table->string('merchant_param5')->nullable();
                $table->string('vault')->nullable();
                $table->string('offer_type')->nullable();
                $table->string('offer_code')->nullable();
                $table->decimal('discount_value', 12, 2)->nullable();
                $table->decimal('mer_amount', 12, 2)->nullable();
                $table->string('eci_value')->nullable();
                $table->integer('retry')->nullable();
                $table->string('response_code')->nullable();
                $table->text('billing_notes')->nullable();
                $table->dateTime('trans_date')->nullable();
                $table->string('bin_country')->nullable();
                $table->text('billing_details')->nullable();
                $table->timestamps();

                $table->index('order_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('cc_avenue_payment');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('woohoo_order_id')->nullable();
                $table->string('order_status')->nullable();
                $table->decimal('denomination', 10, 2)->nullable();
                $table->string('sender_first_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->string('sender_phone_no')->nullable();
                $table->string('sender_post_code')->nullable();
                $table->text('sender_address_1')->nullable();
                $table->text('sender_address_2')->nullable();
                $table->string('sender_city')->nullable();
                $table->string('sender_state')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('receiver_name')->nullable();
                $table->string('receiver_email')->nullable();
                $table->string('receiver_mobile')->nullable();
                $table->text('receiver_msg')->nullable();
                $table->json('cards')->nullable();
                $table->boolean('order_cancel')->default(false);
                $table->string('order_payment')->nullable();
                $table->string('currency')->nullable();
                $table->json('additionalTxnFields')->nullable();
                $table->decimal('grand_payable_amount', 10, 2)->nullable();
                $table->decimal('discounted_amount_value', 10, 2)->nullable();
                $table->decimal('amount_payable_after_discount', 10, 2)->nullable();
                $table->string('gst_number')->nullable();
                $table->string('country')->nullable();
                $table->uuid('merchant_order_id')->nullable()->unique();
                $table->string('refno')->nullable();
                $table->string('product_name')->nullable();
                $table->integer('quantity')->default(1);
                $table->string('gift_send_option')->nullable();
                $table->string('delivery_mode')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('sku');
                $table->index('order_status');
                $table->index('merchant_order_id');
                $table->index('created_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KGenOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('k_gen_orders')) {
            Schema::create('k_gen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('variant_id');
            $table->string('external_ref')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('payable_amount', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0)->nullable();
            $table->string('status')->default('PENDING_PAYMENT');
            $table->string('payment_status')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->json('api_response')->nullable();
            $table->json('vouchers')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
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
        Schema::dropIfExists('k_gen_orders');
    }
}

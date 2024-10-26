<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSummaryTable extends Migration
{
    public function up()
    {
        Schema::create('order_summary', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID for the order_summary table

            // Use integer instead of foreignId for compatibility
            $table->integer('order_id')->unsigned(); // Match with qs_orders.id
            $table->integer('payment_id')->unsigned(); // Match with cc_avenue_payment.id

            $table->string('product_name')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('order_status')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('qs_orders')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('cc_avenue_payment')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_summary');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSummaryTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('order_summary')) {
            Schema::create('order_summary', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('payment_gateway')->nullable();
                $table->string('product_name')->nullable();
                $table->string('sender_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->string('sender_phone')->nullable();
                $table->string('payment_status')->nullable();
                $table->string('order_status')->nullable();
                $table->string('summary_status')->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('order_summary');
    }
}

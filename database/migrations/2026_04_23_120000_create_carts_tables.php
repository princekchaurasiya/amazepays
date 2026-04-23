<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->foreignId('gift_theme_id')->nullable()->constrained('gift_card_themes')->nullOnDelete();
            $table->string('product_slug');
            $table->string('sku');
            $table->string('product_name');
            $table->string('gift_send_option', 32);
            $table->decimal('denomination', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->string('gift_message_title', 120)->nullable();
            $table->string('gift_delivery_option', 32)->nullable();
            $table->timestamp('gift_delivery_at')->nullable();
            $table->string('sender_first_name', 120)->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_mobile', 20)->nullable();
            $table->text('receiver_msg')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['cart_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};

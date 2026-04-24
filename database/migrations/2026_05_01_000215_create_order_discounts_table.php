<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every discount applied to an order line (SKU discount, cart offer, loyalty, bank offer, etc.).
 * Each row is an immutable breakdown entry; order.discount_total_minor = SUM(amount_minor).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('source_type', ['product_discount', 'offer', 'bank_offer', 'loyalty_redemption', 'manual', 'coupon'])->index();
            $table->unsignedBigInteger('source_ref_id')->nullable();
            $table->string('code', 64)->nullable();
            $table->string('label');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamps();

            $table->index(['order_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_discounts');
    }
};

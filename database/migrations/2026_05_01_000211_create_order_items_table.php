<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order line items. Denormalized name/sku for immutability - if product is later edited,
 * historical orders still show the original values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('denomination_id')->nullable()->constrained('product_price_denominations')->cascadeOnUpdate()->nullOnDelete();
            $table->string('sku_snapshot', 128);
            $table->string('name_snapshot');
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_amount_minor');
            $table->bigInteger('line_subtotal_minor');
            $table->bigInteger('line_discount_minor')->default(0);
            $table->bigInteger('line_tax_minor')->default(0);
            $table->bigInteger('line_total_minor');
            $table->char('currency', 3)->default('INR');
            $table->enum('fulfilment_status', ['pending', 'fulfilled', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->enum('source_distributor', ['value_design', 'kgen', 'lysto', 'woohoo', 'vouchagram'])->nullable();
            $table->string('distributor_brand_code', 128)->nullable();
            $table->string('distributor_product_ref', 128)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
            $table->index(['order_id', 'fulfilment_status']);
            $table->index(['source_distributor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

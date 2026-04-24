<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materialized read-model for fast admin/list queries (item count, brand summary, gateway summary).
 * Rebuilt by OrderSummaryProjector on status transitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('distinct_product_count')->default(0);
            $table->string('primary_brand_name')->nullable();
            $table->string('primary_gateway', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('fulfilment_status', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('primary_gateway');
            $table->index(['payment_status', 'fulfilment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_summaries');
    }
};

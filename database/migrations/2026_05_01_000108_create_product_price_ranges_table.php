<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Open-range SKU pricing (min..max with optional step). 1:1 with products.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->bigInteger('min_amount_minor');
            $table->bigInteger('max_amount_minor');
            $table->bigInteger('step_amount_minor')->default(100);
            $table->char('currency', 3)->default('INR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_ranges');
    }
};

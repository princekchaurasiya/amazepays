<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixed-amount SKU options for gift cards (e.g. 500 / 1000 / 2000). Money stored as minor units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Explicitly name the index to stay within MySQL identifier limits.
            $table->unique(['product_id', 'amount_minor', 'currency'], 'ppd_prod_amt_cur_uniq');
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_denominations');
    }
};

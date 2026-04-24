<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront eligibility/visibility rules per product (home sections, featured windows, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_display_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('show_on_homepage')->default(false);
            $table->boolean('show_in_search')->default(true);
            $table->boolean('show_in_b2c_storefront')->default(true);
            $table->boolean('show_in_b2b_portal')->default(false);
            $table->unsignedInteger('homepage_order')->default(0);
            $table->timestamp('featured_from')->nullable();
            $table->timestamp('featured_until')->nullable();
            $table->timestamps();

            $table->index(['show_on_homepage', 'homepage_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_display_rules');
    }
};

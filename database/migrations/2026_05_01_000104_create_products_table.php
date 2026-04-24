<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core product record. ALL optional/long-text/multi-valued data lives in child tables.
 * hsn_sac_code_id FK is wired in the cross-domain FK migration (after tax tables exist).
 * brand_id FK is added here because brands table already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('sku', 128);
            $table->string('name');
            $table->string('slug', 191);
            $table->string('source_provider', 32)->nullable();
            $table->string('source_product_id')->nullable();
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['active', 'draft', 'archived', 'sunset'])->default('draft');
            $table->enum('type', ['gift_card', 'evc', 'voucher', 'subscription', 'physical'])->default('gift_card');
            $table->enum('delivery_mode', ['digital', 'physical', 'both'])->default('digital');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_b2b_only')->default(false);
            $table->boolean('is_b2c_only')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedBigInteger('hsn_sac_code_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'slug']);
            $table->unique(['source_provider', 'source_product_id']);
            $table->index(['tenant_id', 'brand_id', 'status']);
            $table->index(['tenant_id', 'status', 'is_featured']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->index('hsn_sac_code_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('promotion_campaigns')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('code', 64)->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_shipping', 'bogo'])->index();
            $table->decimal('discount_percent', 6, 3)->nullable();
            $table->bigInteger('discount_amount_minor')->nullable();
            $table->bigInteger('max_discount_amount_minor')->nullable();
            $table->bigInteger('min_cart_amount_minor')->default(0);
            $table->enum('applies_to', ['cart', 'line_item', 'shipping'])->default('cart');
            $table->enum('stackable', ['never', 'with_product_discounts', 'with_bank_offers', 'any'])->default('never');
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'ended', 'archived'])->default('draft');
            $table->boolean('requires_code')->default(true);
            $table->unsignedInteger('usage_limit_global')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

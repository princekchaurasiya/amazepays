<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('promotion_campaigns')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('bank_issuer_id')->nullable()->constrained('bank_issuers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('short_description', 512)->nullable();
            $table->enum('offer_type', ['instant_discount', 'cashback', 'no_cost_emi', 'low_cost_emi'])->index();
            $table->decimal('discount_percent', 6, 3)->nullable();
            $table->bigInteger('discount_amount_minor')->nullable();
            $table->bigInteger('max_discount_amount_minor')->nullable();
            $table->bigInteger('min_transaction_amount_minor')->default(0);
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'ended'])->default('draft');
            $table->enum('stackable', ['never', 'with_product_discounts', 'with_offers', 'any'])->default('never');
            $table->unsignedInteger('usage_limit_global')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'starts_at']);
            $table->index(['bank_issuer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_offers');
    }
};

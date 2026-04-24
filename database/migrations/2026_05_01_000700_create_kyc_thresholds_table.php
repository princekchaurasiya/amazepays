<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC policy thresholds (e.g. "orders >= INR 50,000 require PAN verification" per PMLA/RBI).
 * Rules live in DB so compliance can update without a deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->enum('scope', ['order', 'cumulative_daily', 'cumulative_monthly', 'cumulative_yearly', 'per_product_type'])->default('order');
            $table->enum('channel', ['all', 'storefront', 'b2b_portal', 'corporate', 'api'])->default('all');
            $table->bigInteger('threshold_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->json('required_document_types');
            $table->enum('enforcement', ['soft_warn', 'block_until_verified', 'review_queue'])->default('block_until_verified');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'effective_from']);
            $table->index(['scope', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_thresholds');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional product-specific overrides of the default HSN/SAC-derived tax rate (e.g. exemptions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tax_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('hsn_sac_code_id')->constrained('hsn_sac_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('override_rate_percent', 6, 3)->nullable();
            $table->boolean('is_exempt')->default(false);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'effective_from']);
            $table->index(['hsn_sac_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tax_assignments');
    }
};

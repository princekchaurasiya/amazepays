<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned tax rate rows (effective_from/to) per HSN/SAC x jurisdiction x component.
 * PricingResolver picks the correct row by (code, jurisdiction, component, effective_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hsn_sac_code_id')->constrained('hsn_sac_codes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('jurisdiction_id')->constrained('tax_jurisdictions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('component', ['cgst', 'sgst', 'igst', 'utgst', 'cess', 'vat', 'other'])->index();
            $table->decimal('rate_percent', 6, 3);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes', 512)->nullable();
            $table->timestamps();

            // Explicitly name the indexes to stay within MySQL identifier limits.
            $table->index(['hsn_sac_code_id', 'jurisdiction_id', 'component', 'effective_from'], 'tax_rate_lookup_idx');
            $table->index(['is_active', 'effective_from', 'effective_until'], 'tax_rate_active_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};

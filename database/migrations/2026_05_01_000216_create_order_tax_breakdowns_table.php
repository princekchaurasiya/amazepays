<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-line per-component tax breakdown (CGST, SGST, IGST, Cess). Derived from tax_rates at resolve time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tax_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('tax_component', ['cgst', 'sgst', 'igst', 'utgst', 'cess', 'vat', 'other'])->index();
            $table->unsignedBigInteger('tax_rate_id')->nullable();
            $table->string('hsn_sac_code', 16)->nullable();
            $table->decimal('rate_percent', 6, 3);
            $table->bigInteger('taxable_amount_minor');
            $table->bigInteger('tax_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tax_breakdowns');
    }
};

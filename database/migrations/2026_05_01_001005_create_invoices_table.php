<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GST-compliant invoice header per order. Line-level breakdown is order_items + order_tax_breakdowns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('invoice_number', 64);
            $table->string('gstin', 32)->nullable();
            $table->string('place_of_supply_code', 8)->nullable();
            $table->string('place_of_supply_label', 128)->nullable();
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('taxable_value_minor')->default(0);
            $table->bigInteger('cgst_minor')->default(0);
            $table->bigInteger('sgst_minor')->default(0);
            $table->bigInteger('igst_minor')->default(0);
            $table->bigInteger('cess_minor')->default(0);
            $table->bigInteger('grand_total_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['draft', 'issued', 'cancelled', 'void'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_url', 512)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'status', 'issued_at']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single consolidated payments table (replaces unlimit_payment, cc_avenue_payment, kgen_*).
 * Gateway enum discriminates provider; raw callback bodies live in payment_events.
 * payment_instrument_id + applied_bank_offer_id FKs are wired in the cross-domain FK migration
 * because payment_instruments + bank_offers are created in a later migration block.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('gateway', ['unlimit', 'ccavenue', 'razorpay', 'mock_razorpay', 'wallet', 'upi', 'netbanking', 'manual'])->index();
            $table->enum('environment', ['sandbox', 'production', 'mock'])->default('production');
            $table->string('merchant_order_id', 128);
            $table->string('gateway_payment_id', 128)->nullable();
            $table->string('gateway_reference', 128)->nullable();
            $table->enum('status', ['initiated', 'pending', 'authorized', 'captured', 'failed', 'cancelled', 'refunded', 'partially_refunded'])->default('initiated');
            $table->enum('method_category', ['card', 'upi', 'netbanking', 'wallet', 'emi', 'cod', 'other'])->nullable();
            $table->string('method_detail', 64)->nullable();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->bigInteger('fee_minor')->default(0);
            $table->bigInteger('tax_on_fee_minor')->default(0);
            $table->bigInteger('settlement_amount_minor')->default(0);
            $table->foreignId('payment_instrument_id')->nullable()->constrained('payment_instruments')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('applied_bank_offer_id')->nullable()->constrained('bank_offers')->cascadeOnUpdate()->nullOnDelete();
            $table->unsignedTinyInteger('emi_tenure_months')->nullable();
            $table->bigInteger('emi_processing_fee_minor')->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->string('failure_reason', 512)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_payment_id']);
            $table->unique(['gateway', 'merchant_order_id']);
            $table->index(['tenant_id', 'order_id', 'status']);
            $table->index(['tenant_id', 'gateway', 'status']);
            $table->index(['tenant_id', 'status', 'captured_at']);
            $table->index(['idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

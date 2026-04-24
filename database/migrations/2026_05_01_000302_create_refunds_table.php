<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refund requests against a payment (full or partial). One payment can have many refunds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('gateway_refund_id', 128)->nullable();
            $table->enum('status', ['requested', 'processing', 'succeeded', 'failed', 'cancelled'])->default('requested');
            $table->enum('reason', ['customer_request', 'fraud', 'duplicate', 'fulfilment_failed', 'cancellation', 'chargeback', 'other'])->default('customer_request');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('failure_code', 64)->nullable();
            $table->string('failure_reason', 512)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('gateway_refund_id');
            $table->index(['tenant_id', 'payment_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

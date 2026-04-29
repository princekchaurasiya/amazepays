<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authoritative order record. Totals stored in minor units. NO billing/shipping/gift fields here -
 * those live in order_billing_snapshots / order_shipping_snapshots / order_gift_details (immutable snapshots).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('sku', 64)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            // Legacy compatibility for storefront checkout UI; source of truth remains order_items unit_amount_minor.
            $table->decimal('denomination', 12, 4)->nullable();
            $table->string('order_number', 64);
            $table->enum('channel', ['storefront', 'b2b_portal', 'corporate', 'api', 'admin'])->default('storefront');
            $table->enum('status', [
                'created', 'pending_payment', 'payment_processing', 'paid', 'fulfilled',
                'partially_fulfilled', 'cancelled', 'refunded', 'partially_refunded', 'failed',
            ])->default('created');
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('discount_total_minor')->default(0);
            $table->bigInteger('tax_total_minor')->default(0);
            $table->bigInteger('grand_total_minor')->default(0);
            $table->bigInteger('amount_paid_minor')->default(0);
            $table->bigInteger('amount_refunded_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['tenant_id', 'status', 'placed_at']);
            $table->index(['tenant_id', 'channel', 'status']);
            $table->index(['product_id']);
            $table->index(['sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

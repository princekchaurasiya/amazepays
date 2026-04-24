<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gateway-neutral fulfilment attempt against an upstream voucher provider (Woohoo / Vouchagram / VD / KGen).
 * Links an order_item to the upstream reference so gift_cards + webhooks can be reconciled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('provider', ['woohoo', 'vouchagram_send', 'vouchagram_pull', 'vd', 'kgen', 'lysto'])->index();
            $table->string('provider_reference', 191)->nullable();
            $table->string('provider_order_id', 191)->nullable();
            $table->enum('status', ['initiated', 'processing', 'succeeded', 'failed', 'cancelled', 'refund_pending', 'refunded'])->default('initiated');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 64)->nullable();
            $table->string('last_error_message', 1024)->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference']);
            $table->unique(['provider', 'provider_order_id']);
            $table->index(['tenant_id', 'provider', 'status']);
            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_orders');
    }
};

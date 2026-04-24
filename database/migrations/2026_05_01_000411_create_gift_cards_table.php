<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issued gift-card instrument (the actual redeemable). Card number + pin use the encrypted cast.
 * One gift_card row per issued instrument. Balance is tracked via gift_card_balance_snapshots.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('provider_order_id')->nullable()->constrained('provider_orders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('provider', ['woohoo', 'vouchagram_send', 'vouchagram_pull', 'vd', 'kgen', 'lysto', 'internal'])->index();
            $table->text('card_number_encrypted')->nullable();
            $table->text('card_pin_encrypted')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('external_card_id', 128)->nullable();
            $table->bigInteger('face_value_minor');
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['issued', 'active', 'redeemed', 'partially_redeemed', 'expired', 'cancelled'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'external_card_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};

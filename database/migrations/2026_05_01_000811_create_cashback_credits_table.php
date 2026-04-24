<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cashback credits awarded to a user (from bank offers, referrals, promotions).
 * Typically realized by crediting the wallet once vesting_completes_at is reached.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashback_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('source_referral_id')->nullable()->constrained('referrals')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('source_bank_offer_id')->nullable()->constrained('bank_offers')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('status', ['pending_vesting', 'available', 'credited_to_wallet', 'expired', 'reversed'])->default('pending_vesting')->index();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamp('awarded_at');
            $table->timestamp('vesting_completes_at')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashback_credits');
    }
};

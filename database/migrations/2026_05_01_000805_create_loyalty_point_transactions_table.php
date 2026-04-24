<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable points ledger. Every earn/spend/expire is an appended row; never updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('direction', ['credit', 'debit'])->index();
            $table->enum('reason', ['order_earn', 'order_redeem', 'referral_bonus', 'signup_bonus', 'manual_adjust', 'expired', 'campaign_bonus', 'refund_clawback'])->index();
            $table->bigInteger('points');
            $table->bigInteger('running_balance');
            $table->nullableMorphs('source');
            $table->string('reference', 128)->nullable();
            $table->string('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'occurred_at']);
            $table->index(['account_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_transactions');
    }
};

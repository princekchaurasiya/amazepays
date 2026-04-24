<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable double-entry wallet ledger. Every movement is a new row; no UPDATE ever.
 * direction = 'credit' adds to balance, 'debit' subtracts. Running balance stored for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('direction', ['credit', 'debit'])->index();
            $table->enum('reason', [
                'load_topup', 'refund', 'cashback', 'loyalty_credit', 'manual_adjust',
                'order_payment', 'order_refund', 'hold_placed', 'hold_released', 'fee',
            ])->index();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->bigInteger('running_balance_minor');
            $table->nullableMorphs('source');
            $table->string('reference', 128)->nullable();
            $table->string('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['wallet_id', 'occurred_at']);
            $table->index(['wallet_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

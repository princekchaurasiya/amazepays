<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closed-loop user wallet. One active wallet per user per currency. Balances are computed from the
 * immutable wallet_transactions ledger; the columns here are cached read models.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->char('currency', 3)->default('INR');
            // Legacy-compatible columns (existing services/tests still reference these)
            $table->decimal('balance', 14, 2)->default(0);
            $table->boolean('is_frozen')->default(false);
            $table->bigInteger('available_balance_minor')->default(0);
            $table->bigInteger('held_balance_minor')->default(0);
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->timestamp('frozen_at')->nullable();
            $table->string('frozen_reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

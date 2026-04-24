<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable history of tier transitions per loyalty account (audit + retroactive rebuild).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tier_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('from_tier_id')->nullable()->constrained('loyalty_tiers')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('to_tier_id')->nullable()->constrained('loyalty_tiers')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('reason', ['upgrade', 'downgrade', 'manual_adjust', 'tier_expired', 'program_change'])->default('upgrade');
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tier_history');
    }
};

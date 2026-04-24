<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redemptions against loyalty_rewards_catalog items. Fulfilment status separate from the point ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('loyalty_rewards_catalog')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('point_transaction_id')->nullable()->constrained('loyalty_point_transactions')->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('points_spent');
            $table->enum('status', ['pending', 'processing', 'fulfilled', 'cancelled', 'failed'])->default('pending')->index();
            $table->string('tracking_reference')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_reward_redemptions');
    }
};

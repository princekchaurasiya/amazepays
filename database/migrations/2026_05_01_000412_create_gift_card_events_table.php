<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provider-level events per gift_card (activation, balance update, expiry, redemption).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('event_type', ['issued', 'activated', 'balance_updated', 'redeemed', 'expired', 'cancelled', 'reissued'])->index();
            $table->bigInteger('delta_amount_minor')->nullable();
            $table->bigInteger('balance_after_minor')->nullable();
            $table->string('external_event_id', 128)->nullable();
            $table->longText('raw_payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['gift_card_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_events');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Balance snapshots for a gift_card at query time (so balance-inquiry UI is fast + auditable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnUpdate()->cascadeOnDelete();
            $table->bigInteger('balance_minor');
            $table->char('currency', 3)->default('INR');
            $table->enum('source', ['issue', 'provider_query', 'redemption', 'manual'])->default('provider_query');
            $table->string('external_query_id', 128)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['gift_card_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_balance_snapshots');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-channel delivery attempt of a gift card to its recipient. Tracks email/SMS/webhook result
 * so the customer-support UI can replay delivery without dumping notification details into gift_cards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained('gift_cards')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('gift_theme_id')->nullable()->constrained('gift_themes')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'download', 'print', 'webhook'])->index();
            $table->string('recipient_identifier');
            $table->enum('status', ['pending', 'scheduled', 'sent', 'failed', 'bounced'])->default('pending');
            $table->string('failure_reason', 512)->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamps();

            $table->index(['gift_card_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_deliveries');
    }
};

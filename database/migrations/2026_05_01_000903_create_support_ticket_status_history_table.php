<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit of every ticket status transition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('reason')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['ticket_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_history');
    }
};

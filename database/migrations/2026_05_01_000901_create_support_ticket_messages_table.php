<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only message thread under a ticket. Author can be customer or agent (author_role).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('author_role', ['customer', 'agent', 'system'])->index();
            $table->enum('visibility', ['public', 'internal_note'])->default('public');
            $table->longText('body');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['ticket_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};

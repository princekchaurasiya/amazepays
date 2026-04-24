<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per deliverable channel (email address, mobile, push token, webhook).
 * Separate from auth identities so a user can receive notifications on channels they don't log in with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_contact_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'push', 'webhook'])->index();
            $table->string('value');
            $table->string('label')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('opted_out')->default(false);
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'value']);
            $table->index(['channel', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_contact_channels');
    }
};

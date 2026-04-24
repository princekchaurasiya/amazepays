<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived codes for email verification (double-opt-in, change-email). Hashed; never log the raw.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('user_auth_identities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash', 128);
            $table->enum('purpose', ['signup_confirm', 'email_change', 're_verify'])->default('signup_confirm');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();

            $table->index(['email', 'expires_at']);
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
    }
};

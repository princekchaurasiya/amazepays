<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-flight OTP codes keyed by identifier. Codes are hashed; consumed_at marks single-use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('identity_id')->nullable()->constrained('user_auth_identities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'voice'])->index();
            $table->enum('purpose', ['login', 'signup', 'password_reset', 'transaction', 'pin_change', 'kyc', 'other'])->index();
            $table->string('identifier');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('request_ip', 64)->nullable();
            $table->timestamps();

            $table->index(['identifier', 'purpose', 'consumed_at']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_otp_codes');
    }
};

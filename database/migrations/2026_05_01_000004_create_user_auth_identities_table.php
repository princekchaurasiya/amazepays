<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per login identifier a user can sign in with (email, mobile, social).
 * A single user can own multiple identities. Unique per (provider, identifier).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_auth_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('provider', ['email', 'mobile', 'google', 'apple', 'facebook', 'otp'])->index();
            $table->string('identifier');
            $table->string('display_identifier')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token', 128)->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'identifier']);
            $table->index(['user_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_auth_identities');
    }
};

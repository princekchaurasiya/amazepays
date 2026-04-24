<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Password / secret hash per identity. Keeps secrets physically separate from user profile columns.
 * Never store plaintext; password_hash is bcrypt/argon2. Lifecycle is owned by Fortify/Auth services.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_auth_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('identity_id')->constrained('user_auth_identities')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('password_hash');
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamp('must_reset_at')->nullable();
            $table->timestamps();

            $table->unique('identity_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_auth_secrets');
    }
};

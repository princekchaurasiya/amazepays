<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembered browsers / devices for MFA short-circuit + fraud detection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('device_fingerprint', 128);
            $table->string('label')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('last_ip', 64)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('trusted_until')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};

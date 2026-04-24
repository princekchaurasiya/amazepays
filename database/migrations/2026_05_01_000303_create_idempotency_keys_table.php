<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-use idempotency tokens for replay-safe POST/PUT/PATCH endpoints.
 * route_key + key_hash is unique so the same Idempotency-Key header can be replayed
 * per route without colliding across unrelated endpoints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('route_key', 128);
            $table->string('key_hash', 128);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->enum('state', ['pending', 'completed', 'errored'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['route_key', 'key_hash']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};

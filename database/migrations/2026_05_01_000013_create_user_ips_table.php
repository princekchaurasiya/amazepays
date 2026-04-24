<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user IP ledger + geolocation enrichment for fraud/risk analytics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ip_address', 64);
            $table->string('country', 2)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('asn', 32)->nullable();
            $table->string('isp')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->unsignedInteger('seen_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ip_address']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ips');
    }
};

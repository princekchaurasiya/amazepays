<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HTTP call log to upstream providers. Never stores secrets (hash only). Used for debugging,
 * replay, and chargeback evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('provider_connections')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sync_run_id')->nullable()->constrained('provider_sync_runs')->cascadeOnUpdate()->nullOnDelete();
            $table->nullableMorphs('initiator');
            $table->string('operation', 64)->index();
            $table->string('endpoint', 512);
            $table->string('http_method', 10);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('request_hash', 128)->nullable();
            $table->longText('request_payload_redacted')->nullable();
            $table->longText('response_payload_redacted')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 1024)->nullable();
            $table->string('upstream_reference', 128)->nullable();
            $table->timestamp('called_at');
            $table->timestamps();

            $table->index(['connection_id', 'operation', 'called_at']);
            $table->index('upstream_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_api_call_logs');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upstream provider credentials (Woohoo, CCAvenue, Unlimit, Razorpay, Vouchagram, VD, Lysto, KGen).
 * Secrets stored as AES-encrypted columns; raw values only ever in-memory via the provider boundary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('provider', [
                'woohoo', 'ccavenue', 'unlimit', 'razorpay', 'mock_razorpay',
                'vouchagram', 'value_design', 'lysto_athena', 'kgen',
            ])->index();
            $table->enum('environment', ['sandbox', 'production', 'mock'])->default('sandbox')->index();
            $table->string('label');
            $table->json('public_config')->nullable();
            $table->text('credentials_encrypted')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->string('callback_url', 512)->nullable();
            $table->string('webhook_url', 512)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_credential_rotated_at')->nullable();
            $table->timestamp('last_successful_call_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'provider', 'environment']);
            $table->index(['provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical'])->default('info')->index();
            $table->enum('event_type', [
                'login_success', 'login_failure', 'otp_failure', 'password_reset',
                'webhook_signature_mismatch', 'suspicious_ip', 'ip_block', 'mobile_block',
                'rate_limit_trip', 'idempotency_violation', 'pin_failure', 'role_grant',
                'kyc_submitted', 'kyc_rejected', 'payment_failure', 'other',
            ])->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('country', 2)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['tenant_id', 'event_type', 'occurred_at']);
            $table->index(['ip_address', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_event_logs');
    }
};

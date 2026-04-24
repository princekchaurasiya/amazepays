<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Security event logs — high-volume security event tracking
        Schema::create('security_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical']);
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_url', 2048)->nullable();
            $table->string('request_method', 10)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_vpn')->default(false);
            $table->string('device_id', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event_type');
            $table->index('severity');
            $table->index('ip_address');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['resolved', 'severity', 'created_at']);
        });

        // Blocked IPs — auto/manual IP blocking with expiry
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->text('reason');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_blocked')->default(false);
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('block_count')->default(1);
            $table->boolean('permanent')->default(false);
            $table->timestamps();

            $table->index('expires_at');
        });

        // Transaction PINs — separate from password, required for financial actions
        Schema::create('transaction_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('pin_hash');
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('pin_changed_at')->nullable();
            $table->timestamps();
        });

        // Trusted devices — device fingerprint tracking per user
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id', 255);
            $table->string('device_name', 255)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index('user_id');
        });

        // Two factor secrets — TOTP secrets for 2FA
        Schema::create('two_factor_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->text('secret');
            $table->text('recovery_codes')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        // Audit logs — comprehensive admin and user action trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 100);
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 2048)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['auditable_type', 'auditable_id']);
        });

        // IP Whitelists — per-tenant API IP whitelist
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'ip_address']);
        });

        // API Keys — for reseller and loyalty program API access
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('key', 64)->unique();
            $table->text('secret');
            $table->enum('type', ['reseller', 'loyalty', 'internal'])->default('reseller');
            $table->unsignedInteger('rate_limit')->default(120);
            $table->json('allowed_ips')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['key']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('ip_whitelists');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('two_factor_secrets');
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('transaction_pins');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('security_event_logs');
    }
};

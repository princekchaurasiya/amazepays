<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core user principal. NO passwords, NO OTPs here; those live in user_auth_secrets / user_otp_codes.
 * tenant_id is NULLABLE only to allow platform-level super-admin accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnUpdate()->nullOnDelete();
            $table->string('display_name');
            $table->enum('account_type', ['customer', 'admin', 'support', 'partner', 'system'])->default('customer');
            $table->enum('status', ['active', 'pending', 'suspended', 'closed'])->default('active');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

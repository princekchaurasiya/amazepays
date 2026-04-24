<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user rolled-up KYC state. Document rows + verification attempts live in sibling tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_review', 'verified', 'rejected', 'expired'])->default('not_started')->index();
            $table->enum('tier', ['minimal', 'standard', 'full'])->default('minimal');
            $table->string('legal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('rejection_reason', 1024)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_profiles');
    }
};

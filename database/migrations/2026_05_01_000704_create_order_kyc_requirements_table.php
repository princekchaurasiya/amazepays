<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable snapshot of KYC requirements computed for an order at placement time.
 * Kept even after KYC passes so audits can reconstruct the exact rule that was in force.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_kyc_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('kyc_threshold_id')->nullable()->constrained('kyc_thresholds')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('kyc_profile_id')->nullable()->constrained('kyc_profiles')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('requirement_status', ['not_required', 'required', 'waived', 'satisfied', 'blocked'])->default('not_required')->index();
            $table->enum('enforcement', ['soft_warn', 'block_until_verified', 'review_queue'])->default('soft_warn');
            $table->json('required_document_types')->nullable();
            $table->bigInteger('evaluated_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->text('reason')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamp('satisfied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_kyc_requirements');
    }
};

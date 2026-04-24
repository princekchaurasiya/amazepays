<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every verification attempt against a KYC document (manual review / external API / self-declared).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_document_id')->constrained('kyc_documents')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('method', ['manual_review', 'automated_ocr', 'provider_api', 'self_declared'])->index();
            $table->enum('outcome', ['pending', 'verified', 'rejected', 'retry'])->default('pending')->index();
            $table->string('provider', 64)->nullable();
            $table->string('provider_reference', 128)->nullable();
            $table->longText('raw_provider_response')->nullable();
            $table->string('notes', 1024)->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['kyc_document_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_document_verifications');
    }
};

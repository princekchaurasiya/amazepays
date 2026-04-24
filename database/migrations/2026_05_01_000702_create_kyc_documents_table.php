<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Submitted KYC documents (PAN / Aadhaar / passport / utility). Number is encrypted at model layer;
 * number_hash stores a keyed hash for dedupe / fraud detection without exposing the raw value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained('kyc_profiles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('document_type', ['pan', 'aadhaar', 'passport', 'voter_id', 'driving_license', 'utility_bill', 'other'])->index();
            $table->text('number_encrypted');
            $table->string('number_hash', 128)->index();
            $table->string('name_on_document')->nullable();
            $table->string('front_image_url', 512)->nullable();
            $table->string('back_image_url', 512)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason', 512)->nullable();
            $table->timestamps();

            $table->index(['kyc_profile_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_issuer_id')->nullable()->constrained('bank_issuers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->enum('category', ['card', 'upi', 'netbanking', 'wallet', 'emi', 'cod', 'other'])->index();
            $table->enum('card_type', ['credit', 'debit', 'prepaid'])->nullable();
            $table->enum('card_network', ['visa', 'mastercard', 'rupay', 'amex', 'diners', 'other'])->nullable();
            $table->boolean('supports_emi')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['bank_issuer_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_instruments');
    }
};

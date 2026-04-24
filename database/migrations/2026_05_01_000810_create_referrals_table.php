<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral edge (referrer -> referee) with fulfilment state machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('referral_programs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('referee_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('qualifying_order_id')->nullable()->constrained('orders')->cascadeOnUpdate()->nullOnDelete();
            $table->string('referral_code', 64);
            $table->enum('status', ['invited', 'signed_up', 'qualified', 'fulfilled', 'expired', 'reversed'])->default('invited')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('signed_up_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'referral_code']);
            $table->index(['referrer_user_id', 'status']);
            $table->index(['referee_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};

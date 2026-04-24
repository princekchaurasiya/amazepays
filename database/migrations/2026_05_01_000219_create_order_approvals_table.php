<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual approval queue for flagged orders (high risk / fraud / B2B credit hold).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('reason', ['fraud_hold', 'kyc_required', 'b2b_credit_hold', 'manual_review', 'amount_threshold'])->index();
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_approvals');
    }
};

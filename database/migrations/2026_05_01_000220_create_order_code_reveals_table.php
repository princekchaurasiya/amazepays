<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit of every time a customer reveals a gift-card PIN/code (for abuse detection + compliance).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_code_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('revealed_at');
            $table->timestamps();

            $table->index(['order_item_id', 'revealed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_code_reveals');
    }
};

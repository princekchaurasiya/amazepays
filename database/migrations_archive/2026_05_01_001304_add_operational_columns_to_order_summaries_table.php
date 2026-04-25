<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns required by payment return flows and legacy checkout until the summary is fully
 * derived from payments + order_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_summaries', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('order_id')->constrained('payments')->cascadeOnUpdate()->nullOnDelete();
            $table->string('product_name')->nullable()->after('payment_id');
            $table->string('sender_name')->nullable()->after('product_name');
            $table->string('sender_email')->nullable()->after('sender_name');
            $table->string('sender_phone', 32)->nullable()->after('sender_email');
            $table->bigInteger('amount_minor')->nullable()->after('sender_phone');

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_summaries', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropIndex(['payment_id']);
            $table->dropColumn([
                'payment_id',
                'product_name',
                'sender_name',
                'sender_email',
                'sender_phone',
                'amount_minor',
            ]);
        });
    }
};

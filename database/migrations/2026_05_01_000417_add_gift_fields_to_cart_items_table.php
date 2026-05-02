<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('gift_theme_id')->nullable()->after('product_id')->constrained('gift_themes')->nullOnDelete();
            $table->string('product_slug')->nullable()->after('gift_theme_id');
            $table->string('sku')->nullable()->after('product_slug');
            $table->string('product_name')->nullable()->after('sku');
            $table->string('gift_send_option')->nullable()->after('product_name');
            $table->decimal('denomination', 15, 2)->nullable()->after('gift_send_option');
            $table->decimal('line_total', 15, 2)->nullable()->after('quantity');
            $table->string('gift_message_title')->nullable()->after('line_total');
            $table->string('gift_delivery_option')->nullable()->after('gift_message_title');
            $table->timestamp('gift_delivery_at')->nullable()->after('gift_delivery_option');
            $table->string('sender_first_name')->nullable()->after('gift_delivery_at');
            $table->string('receiver_name')->nullable()->after('sender_first_name');
            $table->string('receiver_email')->nullable()->after('receiver_name');
            $table->string('receiver_mobile')->nullable()->after('receiver_email');
            $table->text('receiver_msg')->nullable()->after('receiver_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['gift_theme_id']);
            $table->dropColumn([
                'gift_theme_id',
                'product_slug',
                'sku',
                'product_name',
                'gift_send_option',
                'denomination',
                'line_total',
                'gift_message_title',
                'gift_delivery_option',
                'gift_delivery_at',
                'sender_first_name',
                'receiver_name',
                'receiver_email',
                'receiver_mobile',
                'receiver_msg',
            ]);
        });
    }
};

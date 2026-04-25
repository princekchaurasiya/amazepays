<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interim columns used by storefront checkout / Woohoo / Unlimit return flows until all
 * reads are routed through order_items, order_billing_snapshots, and provider_orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refno', 64)->nullable()->after('placed_at');
            $table->string('merchant_order_id', 128)->nullable()->after('refno');
            $table->string('woohoo_order_id', 191)->nullable()->after('merchant_order_id');
            $table->string('order_status', 32)->nullable()->after('woohoo_order_id');
            $table->string('invoice_number', 64)->nullable()->after('order_status');
            $table->string('product_name')->nullable()->after('invoice_number');
            $table->string('sku', 128)->nullable()->after('product_name');
            $table->unsignedInteger('quantity')->nullable()->after('sku');
            $table->decimal('denomination', 14, 4)->nullable()->after('quantity');
            $table->decimal('price', 14, 4)->nullable()->after('denomination');
            $table->decimal('amount_payable_after_discount', 14, 4)->nullable()->after('price');
            $table->decimal('grand_payable_amount', 14, 4)->nullable()->after('amount_payable_after_discount');
            $table->longText('cards')->nullable()->after('grand_payable_amount');
            $table->json('order_cancel')->nullable()->after('cards');
            $table->json('order_payment')->nullable()->after('order_cancel');
            $table->json('additional_txn_fields')->nullable()->after('order_payment');
            $table->json('woohoo_currency_snapshot')->nullable()->after('additional_txn_fields');

            $table->unique(['tenant_id', 'merchant_order_id']);
            $table->index(['tenant_id', 'refno']);
            $table->index('woohoo_order_id');
            $table->index('order_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'merchant_order_id']);
            $table->dropIndex(['tenant_id', 'refno']);
            $table->dropIndex(['woohoo_order_id']);
            $table->dropIndex(['order_status']);
            $table->dropColumn([
                'refno',
                'merchant_order_id',
                'woohoo_order_id',
                'order_status',
                'invoice_number',
                'product_name',
                'sku',
                'quantity',
                'denomination',
                'price',
                'amount_payable_after_discount',
                'grand_payable_amount',
                'cards',
                'order_cancel',
                'order_payment',
                'additional_txn_fields',
                'woohoo_currency_snapshot',
            ]);
        });
    }
};

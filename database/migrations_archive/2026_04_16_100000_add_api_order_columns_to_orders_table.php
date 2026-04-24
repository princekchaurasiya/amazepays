<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns for API / B2B OrderCreationService (alongside legacy Woohoo fields).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('orders', 'product_id')) {
                $table->unsignedInteger('product_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status', 32)->nullable()->after('order_status')->index();
            }
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 32)->nullable()->after('order_payment');
            }
            if (! Schema::hasColumn('orders', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->nullable()->after('grand_payable_amount');
            }
            if (! Schema::hasColumn('orders', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('grand_total');
            }
            if (! Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('orders', 'discount_percentage')) {
                $table->decimal('discount_percentage', 8, 2)->nullable()->after('subtotal');
            }
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->nullable()->after('discount_percentage');
            }
            if (! Schema::hasColumn('orders', 'gst_percentage')) {
                $table->decimal('gst_percentage', 8, 2)->nullable()->after('discount_amount');
            }
            if (! Schema::hasColumn('orders', 'gst_amount')) {
                $table->decimal('gst_amount', 12, 2)->nullable()->after('gst_percentage');
            }
            if (! Schema::hasColumn('orders', 'offer_code')) {
                $table->string('offer_code')->nullable()->after('gst_amount');
            }
            if (! Schema::hasColumn('orders', 'gift_option')) {
                $table->string('gift_option', 64)->nullable()->after('offer_code');
            }
            if (! Schema::hasColumn('orders', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->unique()->after('gift_option');
            }
            if (! Schema::hasColumn('orders', 'billing_name')) {
                $table->string('billing_name')->nullable()->after('idempotency_key');
            }
            if (! Schema::hasColumn('orders', 'billing_email')) {
                $table->string('billing_email')->nullable()->after('billing_name');
            }
            if (! Schema::hasColumn('orders', 'billing_tel')) {
                $table->string('billing_tel')->nullable()->after('billing_email');
            }
            if (! Schema::hasColumn('orders', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('billing_tel');
            }
            if (! Schema::hasColumn('orders', 'billing_address_two')) {
                $table->text('billing_address_two')->nullable()->after('billing_address');
            }
            if (! Schema::hasColumn('orders', 'billing_city')) {
                $table->string('billing_city')->nullable()->after('billing_address_two');
            }
            if (! Schema::hasColumn('orders', 'billing_state')) {
                $table->string('billing_state')->nullable()->after('billing_city');
            }
            if (! Schema::hasColumn('orders', 'billing_zip')) {
                $table->string('billing_zip')->nullable()->after('billing_state');
            }
            if (! Schema::hasColumn('orders', 'billing_country')) {
                $table->string('billing_country', 4)->nullable()->after('billing_zip');
            }
            if (! Schema::hasColumn('orders', 'billing_gst_number')) {
                $table->string('billing_gst_number')->nullable()->after('billing_country');
            }
            if (! Schema::hasColumn('orders', 'voucher_code')) {
                $table->string('voucher_code')->nullable()->after('billing_gst_number');
            }
            if (! Schema::hasColumn('orders', 'voucher_pin')) {
                $table->string('voucher_pin')->nullable()->after('voucher_code');
            }
            if (! Schema::hasColumn('orders', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('voucher_pin');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'order_number', 'product_id', 'status', 'payment_method', 'grand_total',
                'unit_price', 'subtotal', 'discount_amount', 'gst_percentage', 'gst_amount',
                'offer_code', 'gift_option', 'idempotency_key', 'billing_name', 'billing_email',
                'billing_tel', 'billing_address', 'billing_address_two', 'billing_city',
                'billing_state', 'billing_zip', 'billing_country', 'billing_gst_number',
                'voucher_code', 'voucher_pin', 'expiry_date',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

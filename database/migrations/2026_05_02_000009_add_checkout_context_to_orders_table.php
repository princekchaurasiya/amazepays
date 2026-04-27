<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('user_id')->constrained('products')->nullOnDelete();
                $table->index(['product_id']);
            }

            if (! Schema::hasColumn('orders', 'sku')) {
                $table->string('sku', 64)->nullable()->after('product_id');
                $table->index(['sku']);
            }

            if (! Schema::hasColumn('orders', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('sku');
            }

            if (! Schema::hasColumn('orders', 'denomination')) {
                // Legacy compatibility for storefront checkout UI; source of truth remains order_items unit_amount_minor.
                $table->decimal('denomination', 12, 4)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'denomination')) {
                $table->dropColumn('denomination');
            }

            if (Schema::hasColumn('orders', 'quantity')) {
                $table->dropColumn('quantity');
            }

            if (Schema::hasColumn('orders', 'sku')) {
                $table->dropIndex(['sku']);
                $table->dropColumn('sku');
            }

            if (Schema::hasColumn('orders', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }
        });
    }
};


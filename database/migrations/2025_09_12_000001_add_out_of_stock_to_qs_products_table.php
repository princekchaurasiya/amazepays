<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qs_products', function (Blueprint $table) {
            if (!Schema::hasColumn('qs_products', 'out_of_stock')) {
                $table->boolean('out_of_stock')->default(false)->after('show_product');
            }
            if (!Schema::hasColumn('qs_products', 'out_of_stock_comment')) {
                $table->string('out_of_stock_comment')->nullable()->after('out_of_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qs_products', function (Blueprint $table) {
            if (Schema::hasColumn('qs_products', 'out_of_stock_comment')) {
                $table->dropColumn('out_of_stock_comment');
            }
            if (Schema::hasColumn('qs_products', 'out_of_stock')) {
                $table->dropColumn('out_of_stock');
            }
        });
    }
};



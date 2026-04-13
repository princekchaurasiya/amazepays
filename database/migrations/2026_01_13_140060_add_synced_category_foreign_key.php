<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSyncedCategoryForeignKey extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('synced_categories')) {
            return;
        }
        if (! Schema::hasColumn('products', 'synced_category_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('synced_category_id')
                ->references('id')
                ->on('synced_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['synced_category_id']);
        });
    }
}

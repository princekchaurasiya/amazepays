<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront brands + products.brand_id FK (requires products table to exist).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_brands')) {
            Schema::create('storefront_brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('logo')->nullable();
                $table->decimal('order', 5, 2)->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->string('og_image', 500)->nullable();
                $table->string('canonical_url', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('products') || ! Schema::hasTable('storefront_brands')) {
            return;
        }

        if (Schema::hasColumn('products', 'brand_id')) {
            $hasFk = false;
            if (DB::getDriverName() === 'mysql') {
                $fks = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'products'
                    AND COLUMN_NAME = 'brand_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $hasFk = $fks !== [];
            }
            if (! $hasFk) {
                Schema::table('products', function (Blueprint $table) {
                    $table->foreign('brand_id')->references('id')->on('storefront_brands')->onDelete('cascade');
                });
            }
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('id');
                $table->foreign('brand_id')->references('id')->on('storefront_brands')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'brand_id')) {
            $hasFk = false;
            if (DB::getDriverName() === 'mysql') {
                $fks = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'products'
                    AND COLUMN_NAME = 'brand_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                $hasFk = $fks !== [];
            }
            if ($hasFk) {
                Schema::table('products', function (Blueprint $table) {
                    $table->dropForeign(['brand_id']);
                });
            }
        }
        Schema::dropIfExists('storefront_brands');
    }
};

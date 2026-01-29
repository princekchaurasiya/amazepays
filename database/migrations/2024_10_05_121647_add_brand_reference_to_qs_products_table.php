<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddBrandReferenceToQsProductsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('qs_products') && Schema::hasTable('amazepay_available_brands')) {
            // Check if brand_id column exists but foreign key doesn't
            if (Schema::hasColumn('qs_products', 'brand_id')) {
                // Check if foreign key already exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'qs_products' 
                    AND COLUMN_NAME = 'brand_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($foreignKeys)) {
                    Schema::table('qs_products', function (Blueprint $table) {
                        $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('cascade');
                    });
                }
            } else {
                Schema::table('qs_products', function (Blueprint $table) {
                    $table->unsignedBigInteger('brand_id')->nullable()->after('id');
                    $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('cascade');
                });
            }
        }
    }

    public function down()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']); // Drop foreign key constraint
            $table->dropColumn('brand_id'); // Remove the brand_id column
        });
    }
}


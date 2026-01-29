<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToQsProductsSku extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_products') && Schema::hasColumn('qs_products', 'sku')) {
            Schema::table('qs_products', function (Blueprint $table) {
                // Add unique constraint on sku column if it doesn't exist
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('qs_products');
                
                $hasUniqueIndex = false;
                foreach ($indexesFound as $index) {
                    if ($index->isUnique() && in_array('sku', $index->getColumns())) {
                        $hasUniqueIndex = true;
                        break;
                    }
                }
                
                if (!$hasUniqueIndex) {
                    $table->unique('sku', 'qs_products_sku_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('qs_products')) {
            Schema::table('qs_products', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('qs_products');
                
                foreach ($indexesFound as $index) {
                    if ($index->getName() === 'qs_products_sku_unique') {
                        $table->dropUnique('qs_products_sku_unique');
                        break;
                    }
                }
            });
        }
    }
}

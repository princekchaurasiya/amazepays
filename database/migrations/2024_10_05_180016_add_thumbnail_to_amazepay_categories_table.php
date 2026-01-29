<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThumbnailToAmazepayCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('amazepay_categories') && !Schema::hasColumn('amazepay_categories', 'thumbnail')) {
            Schema::table('amazepay_categories', function (Blueprint $table) {
                $table->string('thumbnail')->nullable()->after('slug'); // Add 'thumbnail' column after 'slug'
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
        Schema::table('amazepay_categories', function (Blueprint $table) {
            $table->dropColumn('thumbnail'); // Remove 'thumbnail' column in case of rollback
        });
    }
}

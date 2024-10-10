<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToHomeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('home', function (Blueprint $table) {
            $table->boolean('section_brand_status')->default(1); // Column for section brand status
            $table->boolean('section_hot_deal_status')->default(1); // Column for section hot deal status
            $table->boolean('section_category_status')->default(1); // Column for section category status
            $table->boolean('section_other_deal_status')->default(1); // Column for section other deal status

            $table->string('section_banner_title')->nullable(); // Column for section banner title
            $table->string('section_brand_title')->nullable(); // Column for section brand title
            $table->string('section_hot_deal_title')->nullable(); // Column for section hot deal title
            $table->string('section_category_title')->nullable(); // Column for section category title
            $table->string('section_other_deal_title')->nullable(); // Column for section other deal title
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('home', function (Blueprint $table) {
            $table->dropColumn([
                'section_brand_status',
                'section_hot_deal_status',
                'section_category_status',
                'section_other_deal_status',
                'section_banner_title',
                'section_brand_title',
                'section_hot_deal_title',
                'section_category_title',
                'section_other_deal_title',
            ]);
        });
    }
}

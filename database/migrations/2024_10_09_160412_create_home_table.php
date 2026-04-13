<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHomeTable extends Migration
{
    public function up()
    {
        Schema::create('home', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean('section_banner_status')->default(1);
            $table->integer('position')->nullable()->default(0);
            $table->boolean('section_brand_status')->default(1);
            $table->boolean('section_hot_deal_status')->default(1);
            $table->boolean('section_category_status')->default(1);
            $table->boolean('section_other_deal_status')->default(1);
            $table->string('section_banner_title')->nullable();
            $table->string('section_brand_title')->nullable();
            $table->string('section_hot_deal_title')->nullable();
            $table->string('section_category_title')->nullable();
            $table->string('section_other_deal_title')->nullable();
            $table->integer('priority_product_to_show')->default(10);
        });
    }

    public function down()
    {
        Schema::dropIfExists('home');
    }
}

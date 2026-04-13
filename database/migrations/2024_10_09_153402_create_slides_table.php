<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidesTable extends Migration
{
    public function up()
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('desktop_image')->nullable();
            $table->string('video')->nullable();
            $table->string('small_header')->nullable();
            $table->string('big_header')->nullable();
            $table->string('cta_value')->nullable();
            $table->string('cta_link')->nullable();
            $table->integer('priority')->nullable();
            $table->string('slider_location')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->string('image_mobile')->nullable();
            $table->string('video_mobile')->nullable();
            $table->string('img_alt_tag')->nullable();
            $table->string('display_on_page')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('custom_url')->nullable();
            $table->string('link_type')->nullable();
            $table->timestamps();
        });

        $fks = [
            ['product_id', 'products', 'id'],
            ['category_id', 'categories', 'id'],
            ['brand_id', 'storefront_brands', 'id'],
        ];
        foreach ($fks as [$column, $on, $ref]) {
            if (! Schema::hasTable($on)) {
                continue;
            }
            Schema::table('slides', function (Blueprint $table) use ($column, $on, $ref) {
                $table->foreign($column)->references($ref)->on($on)->nullOnDelete();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('slides');
    }
}

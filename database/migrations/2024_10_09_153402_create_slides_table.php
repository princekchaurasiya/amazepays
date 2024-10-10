<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->string('small_header')->nullable();
            $table->string('big_header')->nullable();
            $table->string('cta_value')->nullable();
            $table->string('cta_link')->nullable();
            $table->integer('priority')->nullable();
            $table->string('slider_location')->nullable();
            $table->timestamps(); // created_at and updated_at fields
            $table->tinyInteger('status')->nullable();
            $table->string('image_mobile')->nullable();
            $table->string('video_mobile')->nullable();
            $table->string('img_alt_tag')->nullable();
            $table->string('display_on_page')->nullable(); // Add this column as required
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slides');
    }
}

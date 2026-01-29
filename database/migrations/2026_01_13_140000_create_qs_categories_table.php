<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQsCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('qs_categories')) {
            Schema::create('qs_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('url')->nullable();
                $table->text('description')->nullable();
                $table->json('images')->nullable();
                $table->integer('subcategoriesCount')->nullable();
                $table->text('subcategories')->nullable();
                $table->timestamps();
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
        if (Schema::hasTable('qs_categories')) {
            Schema::dropIfExists('qs_categories');
        }
    }
}

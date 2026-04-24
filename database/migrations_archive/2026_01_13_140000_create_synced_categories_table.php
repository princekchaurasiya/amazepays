<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedCategoriesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('synced_categories')) {
            Schema::create('synced_categories', function (Blueprint $table) {
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

    public function down(): void
    {
        Schema::dropIfExists('synced_categories');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fallback when Voyager’s categories migration is not present; legacy patches when an older categories row exists.
 */
class CreateCategoriesTableWhenMissing extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent_id')->unsigned()->nullable()->default(null);
                $table->foreign('parent_id')->references('id')->on('categories')->onUpdate('cascade')->onDelete('set null');
                $table->decimal('order', 5, 2)->default(1);
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('thumbnail')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->string('og_image', 500)->nullable();
                $table->string('canonical_url', 500)->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('categories', 'thumbnail')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('thumbnail')->nullable();
            });
        }

        if (! Schema::hasColumn('categories', 'meta_title')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->string('og_image', 500)->nullable();
                $table->string('canonical_url', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Shared with Voyager — do not drop.
    }
}

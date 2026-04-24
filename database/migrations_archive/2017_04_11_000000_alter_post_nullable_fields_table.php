<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterPostNullableFieldsTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('posts')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            foreach (['excerpt', 'meta_description', 'meta_keywords'] as $column) {
                if (Schema::hasColumn('posts', $column)) {
                    DB::statement("ALTER TABLE `posts` MODIFY `{$column}` TEXT NULL");
                }
            }

            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'excerpt')) {
                $table->text('excerpt')->nullable()->change();
            }
            if (Schema::hasColumn('posts', 'meta_description')) {
                $table->text('meta_description')->nullable()->change();
            }
            if (Schema::hasColumn('posts', 'meta_keywords')) {
                $table->text('meta_keywords')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('posts')) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->text('excerpt')->nullable(false)->change();
            $table->text('meta_description')->nullable(false)->change();
            $table->text('meta_keywords')->nullable(false)->change();
        });
    }
}

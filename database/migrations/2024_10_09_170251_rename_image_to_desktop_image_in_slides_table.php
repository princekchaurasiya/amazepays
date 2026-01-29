<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameImageToDesktopImageInSlidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('slides') && Schema::hasColumn('slides', 'image') && !Schema::hasColumn('slides', 'desktop_image')) {
            Schema::table('slides', function (Blueprint $table) {
                $table->renameColumn('image', 'desktop_image');
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
        Schema::table('slides', function (Blueprint $table) {
            $table->renameColumn('desktop_image', 'image');
        });
    }
}

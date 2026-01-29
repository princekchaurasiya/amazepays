<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionToHomeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('home') && !Schema::hasColumn('home', 'position')) {
            Schema::table('home', function (Blueprint $table) {
                $table->integer('position')->nullable()->default(0); // Add position column
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
        Schema::table('home', function (Blueprint $table) {
            $table->dropColumn('position'); // Drop position column
        });
    }
}

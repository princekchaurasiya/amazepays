<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccessTokenColumnInApiTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('api_tokens') && Schema::hasColumn('api_tokens', 'access_token')) {
            Schema::table('api_tokens', function (Blueprint $table) {
                $table->longText('access_token')->change();
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
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('access_token', 255)->change();
        });
    }
}

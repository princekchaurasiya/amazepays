<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUserIdFromOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Remove the user_id column from the otps table
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add back the user_id column to the otps table if you ever need to rollback
        Schema::table('otps', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
        });
    }
}

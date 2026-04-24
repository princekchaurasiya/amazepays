<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvcStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evc_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();
            $table->string('request_ref_no')->index();
            $table->string('status');
            $table->json('details')->nullable(); // optional response payload
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evc_statuses');
    }
}

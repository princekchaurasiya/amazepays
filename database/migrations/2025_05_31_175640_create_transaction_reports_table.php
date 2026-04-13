<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionReportsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_reports', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('report_type');
            $table->string('request_id');
            $table->time('time');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_reports');
    }
}

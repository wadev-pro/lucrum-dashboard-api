<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');
            $table->string('type');
            $table->string('filter');
            $table->string('filename')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('log')->nullable();
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
        Schema::dropIfExists('report_statuses');
    }
}

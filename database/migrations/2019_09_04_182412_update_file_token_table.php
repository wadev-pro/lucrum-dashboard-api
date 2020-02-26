<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateFileTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('user_id');
            $table->dropColumn('filter');
            $table->integer('report_id')->unsigned()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('file_tokens', function (Blueprint $table) {
            $table->tinyInteger('type');
            $table->string('user_id');
            $table->string('filter');
            $table->dropColumn('report_id');
        });
    }
}

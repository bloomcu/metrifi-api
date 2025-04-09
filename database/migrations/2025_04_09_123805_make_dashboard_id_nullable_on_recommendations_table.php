<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign(['dashboard_id']);
            $table->foreignId('dashboard_id')->nullable()->change();
            $table->foreign('dashboard_id')->references('id')->on('dashboards');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign(['dashboard_id']);
            $table->foreignId('dashboard_id')->nullable(false)->change();
            $table->foreign('dashboard_id')->references('id')->on('dashboards');
        });
    }
};

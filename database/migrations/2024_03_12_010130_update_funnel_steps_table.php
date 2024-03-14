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
        Schema::table('funnel_steps', function (Blueprint $table) {
            $table->dropColumn('metric');

            $table->json('metrics')->after('measurables')->nullable();
            $table->dropColumn('measurables');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('funnel_steps', function (Blueprint $table) {
            $table->text('metric')->after('order')->nullable();

            $table->json('measurables')->after('metrics')->nullable();
            $table->dropColumn('metrics');
        });
    }
};

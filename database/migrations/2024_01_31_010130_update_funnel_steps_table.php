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
            $table->text('metric')->after('type')->nullable();
            $table->json('measurables')->after('expression')->nullable();
            $table->dropColumn(['type', 'expression']);
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
            $table->json('type')->after('metric')->nullable();
            $table->dropColumn('metric');

            $table->json('expression')->after('measurables')->nullable();
            $table->dropColumn('measurables');
        });
    }
};

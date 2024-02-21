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
        Schema::table('funnels', function (Blueprint $table) {
            $table->dropColumn('automating');
            $table->dropColumn('automation_msg');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('funnels', function (Blueprint $table) {
            $table->boolean('automating')->after('slug')->default(false);
            $table->string('automation_msg')->after('automating')->nullable();
        });
    }
};

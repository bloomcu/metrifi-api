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
        Schema::table('analyses', function (Blueprint $table) {
            $table->bigInteger('subject_funnel_conversion_value')->change();
            $table->bigInteger('subject_funnel_assets')->change();
            $table->bigInteger('subject_funnel_potential_assets')->change();
            $table->bigInteger('bofi_asset_change')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->integer('subject_funnel_conversion_value')->change();
            $table->integer('subject_funnel_assets')->change();
            $table->integer('subject_funnel_potential_assets')->change();
            $table->integer('bofi_asset_change')->change();
        });
    }
};

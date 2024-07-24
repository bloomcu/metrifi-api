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
            $table->decimal('subject_funnel_performance', 8, 2)->change();
            $table->decimal('bofi_performance', 8, 2)->change();
            $table->integer('bofi_asset_change')->change();
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
            $table->decimal('subject_funnel_performance')->change();
            $table->decimal('bofi_performance')->change();
            $table->decimal('bofi_asset_change')->change();
        });
    }
};

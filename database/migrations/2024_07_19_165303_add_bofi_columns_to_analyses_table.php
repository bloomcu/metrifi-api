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
            $table->decimal('subject_funnel_assets')->after('subject_funnel_performance')->nullable();
            $table->integer('bofi_step_index')->after('subject_funnel_assets')->nullable();
            $table->string('bofi_step_name')->after('bofi_step_index')->nullable();
            $table->decimal('bofi_performance')->after('bofi_step_name')->nullable();
            $table->decimal('bofi_asset_change')->after('bofi_performance')->nullable();
            $table->string('period')->after('meta')->nullable();
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
            $table->dropColumn('subject_funnel_assets');
            $table->dropColumn('bofi_step_id');
            $table->dropColumn('bofi_performance');
            $table->dropColumn('bofi_asset_change');
            $table->dropColumn('period');
        });
    }
};

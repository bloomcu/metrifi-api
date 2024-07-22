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
            $table->dropColumn('subject_funnel_assets');
            $table->dropColumn('bofi_step_name');
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
            $table->decimal('subject_funnel_assets')->after('subject_funnel_performance')->nullable();
            $table->string('bofi_step_name')->after('bofi_step_index')->nullable();
        });
    }
};

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
            $table->decimal('subject_funnel_performance')->after('in_progress')->nullable();
            $table->timestamp('start_date')->after('content');
            $table->timestamp('end_date')->after('start_date');
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
            $table->dropColumn('subject_funnel_performance');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }
};

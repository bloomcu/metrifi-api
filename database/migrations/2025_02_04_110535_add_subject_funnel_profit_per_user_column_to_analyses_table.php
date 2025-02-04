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
          $table->decimal('subject_funnel_profit_per_user', 16, 2)->after('subject_funnel_potential_assets')->default(0);
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
          $table->dropColumn('subject_funnel_profit_per_user');
        });
    }
};

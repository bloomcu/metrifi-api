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
            $table->index(['dashboard_id', 'type', 'deleted_at', 'created_at'], 'analyses_dashboard_type_deleted_created_index');
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
            $table->dropIndex('analyses_dashboard_type_deleted_created_index');
        });
    }
};

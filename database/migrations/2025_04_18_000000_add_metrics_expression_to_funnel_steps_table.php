<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funnel_steps', function (Blueprint $table) {
            $table->string('metrics_expression')
                  ->after('metrics')
                  ->default('orGroup')
                  ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('funnel_steps', function (Blueprint $table) {
            $table->dropColumn('metrics_expression');
        });
    }
};

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
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id');
            $table->foreignId('subject_funnel_id');
            $table->boolean('in_progress')->default(true);
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign constraints
            $table->foreign('dashboard_id')->references('id')->on('dashboards');
            // $table->foreign('subject_funnel_id')->references('id')->on('funnels');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analyses');
    }
};

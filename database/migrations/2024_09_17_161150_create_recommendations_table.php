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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id');
            $table->boolean('in_progress')->default(true);
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('prototype')->nullable();
            $table->string('period')->nullable();
            $table->timestamps();

            // Foreign constraints
            $table->foreign('dashboard_id')->references('id')->on('dashboards');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recommendations');
    }
};

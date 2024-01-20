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
        Schema::create('funnel_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id');
            $table->string('type'); // TODO: We can move this into the express json?
            $table->integer('order')->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->json('expression')->nullable();
            $table->timestamps();

            // Foreign constraints
            $table->foreign('funnel_id')->references('id')->on('funnels');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('funnel_steps');
    }
};

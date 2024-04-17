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
        Schema::create('funnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->foreignId('user_id');
            $table->foreignId('connection_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('zoom')->default(0);
            $table->integer('conversion_value')->default(0);
            $table->json('projections')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('funnels');
    }
};

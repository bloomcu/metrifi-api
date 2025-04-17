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
        Schema::create('block_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id');
            $table->foreignId('organization_id');
            $table->foreignId('user_id');
            $table->integer('version_number');
            $table->longText('data');
            $table->timestamps();

            // Foreign constraints
            $table->foreign('block_id')->references('id')->on('blocks')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('block_versions');
    }
};

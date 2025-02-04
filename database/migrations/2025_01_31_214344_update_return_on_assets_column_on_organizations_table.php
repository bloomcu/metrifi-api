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
      Schema::table('organizations', function (Blueprint $table) {
        // Change the column type from integer to decimal(5,2)
        $table->decimal('return_on_assets', 5, 2)->default(1.00)->change();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('organizations', function (Blueprint $table) {
        // Revert the column back to integer
        $table->integer('return_on_assets')->default(1)->change();
      });
    }
};

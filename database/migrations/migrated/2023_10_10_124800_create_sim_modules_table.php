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
        Schema::create('sim_modules', function (Blueprint $table) {
            $table->id();
            $table->string('sim_number',14)->unique()->index();
            $table->string('sim_port_number',1)->unique()->index();
            $table->boolean('current_port_state')->index(); //this factor determines if the sim card is greyed out or set active by choice of color
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sim_modules');
    }
};

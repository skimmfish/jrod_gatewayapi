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
        Schema::create('contact_models', function (Blueprint $table) {
            $table->id();
            $table->string('contact_no',14)->nullable()->index();
            $table->string('contact_alt_number',14)->index()->nullable();
            $table->string('contact_fname',255)->nullable()->unique()->index();
            $table->string('contact_lname')->nullable()->index;
            $table->boolean('contact_state')->index(); //this could be archived/blocked from sms/deleted/active
            $table->integer('sim_contact_saved_to')->unsigned()->index(); //this could be any of the sim ports located by port/number
            $table->foreign('sim_contact_saved_to')->references('id')->on('sim_modules');
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
        Schema::dropIfExists('contact_models');
    }
};

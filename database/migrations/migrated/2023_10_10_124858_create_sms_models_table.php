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
        Schema::create('sms_models', function (Blueprint $table) {
            $table->id();
            $table->string('sim_number_sent_to',14)->nullable()->index();
            $table->string('_msg',163)->nullable();
            $table->string('msg_type',10)->index();
            $table->string('msg_sender_no')->index();
            $table->tinyInteger('msg_activity_state')->index();
            $table->boolean('active_state')->index();
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
        Schema::dropIfExists('sms_models');
    }
};

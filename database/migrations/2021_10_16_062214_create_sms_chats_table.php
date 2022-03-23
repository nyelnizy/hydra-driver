<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hydra_sms_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->string('device_id');
            $table->string('sim_serial');
            $table->string('participant_address');
            $table->string('participant_name');
            $table->string('participant_initials')->nullable();
            $table->string('avatar_bg_color');
            $table->string('subscription_id')->nullable();
            $table->string('last_message')->nullable();
            $table->string('last_message_status')->nullable();
            $table->string('last_message_id_mf')->nullable();
            $table->boolean('has_attachment');
            $table->boolean('can_reply');
            $table->dateTime('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_chats');
    }
}

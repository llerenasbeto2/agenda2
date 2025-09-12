<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations_sports', function (Blueprint $table) {
            /*$table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('full_name', 255);
            $table->string('phone', 50);
            $table->foreignId('sports_id')->constrained('sports_fields')->onDelete('cascade');
            $table->string('event_title', 255);
            $table->foreignId('event_type')->constrained('events')->onDelete('cascade');
            $table->integer('attendees');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('recurring_frequency')->nullable();
            $table->json('recurring_days')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->json('irregular_dates')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();*/
            $table->id();
            /*
            $table->bigInteger('user_id')->unsigned();
            $table->string('full_name', 255);
            $table->string('phone', 255);
            $table->unsignedBigInteger('sports_id');
            $table->string('event_title', 255);
            $table->bigInteger('event_type');
            $table->integer('attendees');
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->text('requirements');
            $table->string('status', 30);
            $table->timestamps();


            
            
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('sports_id')->references('id')->on('sport_field');*/
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations_sports');
 
    }
};

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
        Schema::create('reservations_classrooms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('full_name', 255);
            $table->string('Email',255);
            $table->string('phone', 255);
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('municipality_id');
            $table->bigInteger('classroom_id')->unsigned();
            $table->string('event_title', 255);
            $table->bigInteger('category_type')->unsigned();
            $table->integer('attendees');
            $table->timestamp('start_datetime');
            $table->datetime('end_datetime')->nullable();
            $table->text('requirements');
            $table->string('status', 30);
            $table->boolean('is_recurring')->default(false);
            $table->integer('repeticion')->nullable();
            $table->string('recurring_frequency', 255)->nullable();
            $table->json('irregular_dates')->nullable();
            $table->timestamp('recurring_end_date')->nullable();
            $table->json('recurring_days')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->date('payment_date')->nullable(); 

            $table->timestamps();
            $table->foreign('municipality_id')->references('id')->on('municipality');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('faculty_id')->references('id')->on('faculties');
            $table->foreign('classroom_id')->references('id')->on('classrooms');
            $table->foreign('category_type')->references('id')->on('categories');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations_classrooms');
    }
};

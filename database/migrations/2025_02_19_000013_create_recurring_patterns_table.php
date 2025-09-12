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
        Schema::create('recurring_patterns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');
            $table->integer('repeticion');
            $table->string('recurring_frequency', 255);
            $table->text('recurring_days');
            $table->timestamp('recurring_end_date');
            $table->text('irregular_dates');

            $table->foreign('reservation_id')->references('id')->on('reservations_classrooms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_patterns');
    }
};

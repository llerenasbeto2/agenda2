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
        Schema::create('complaints', function (Blueprint $table) {
           /* $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('space_type');
            $table->text('complaint_text');
            $table->timestamp('created_at')->useCurrent();*/
            $table->id();
            $table->text('name_user');
            $table->unsignedBigInteger('sports_id');
            $table->text('complaint_text');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->foreign('sports_id')->references('id')->on('sport_field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};

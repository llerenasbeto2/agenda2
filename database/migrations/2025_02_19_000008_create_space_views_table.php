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
        Schema::create('space_views', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('class_facul_id')->nullable();
            $table->text('location');
            $table->integer('capacity');
            $table->text('services');
            $table->string('responsible', 255);
            $table->string('email', 255);
            $table->string('phone', 50);
            //$table->unsignedBigInteger('image')->nullable();
            //$table->string('image', 255)->nullable()->change();
            $table->string('image', 255)->nullable();
            $table->timestamps();


           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_views');
        //$table->unsignedBigInteger('image')->nullable()->change();
    }
};

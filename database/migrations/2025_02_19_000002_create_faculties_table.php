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
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('location');
            $table->string('responsible', 255)->index();
            $table->string('email', 255);
            $table->string('phone', 50);
           // $table->integer('municipality_id')->unsigned();
           
            $table->unsignedBigInteger('municipality_id');
            $table->foreign('municipality_id')->references('id')->on('municipality');
            $table->integer('capacity')->nullable()->change();
            $table->longtext('services');
            $table->longtext('description');
            $table->string('image', 255)->nullable();
            $table->string('web_site', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->string('responsible')->nullable()->change();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('responsible')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->string('responsible')->nullable(false)->change();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('responsible')->nullable(false)->change();
        });
    }
};
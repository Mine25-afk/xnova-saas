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
        Schema::create('botones', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre');
            $table->unsignedSmallInteger('pantalla_id');
            $table->foreign('pantalla_id')->references('id')->on('pantallas');
            $table->string('codigo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('botones');
    }
};

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
        Schema::create('pantallas', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre');
            $table->unsignedSmallInteger('modulo_id');
            $table->foreign('modulo_id')->references('id')->on('modulos');
            $table->string('codigo')->unique();
            $table->string('ruta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantallas');
    }
};

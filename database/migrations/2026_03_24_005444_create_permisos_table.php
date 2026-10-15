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
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('modulo_id');
            $table->foreign('modulo_id')->references('id')->on('modulos');
            $table->unsignedSmallInteger('pantalla_id');
            $table->foreign('pantalla_id')->references('id')->on('pantallas');
            $table->unsignedSmallInteger('boton_id')->nullable();
            $table->foreign('boton_id')->references('id')->on('botones');
            $table->string('nombre', 100);
            $table->string('codigo', 100)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};

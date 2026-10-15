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
        Schema::create('horarios_detalles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('nombre');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_salida')->nullable();
            $table->time('receso_incio')->nullable();
            $table->time('receso_salida')->nullable();
            $table->unsignedSmallInteger('horario_id');
            $table->foreign('horario_id')->references('id')->on('horarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_detalles');
    }
};

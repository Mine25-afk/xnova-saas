<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->string('cliente_dni');
            $table->foreign('cliente_dni')->references('dni')->on('socio_negocios');

            $table->string('colaborador_dni');
            $table->foreign('colaborador_dni')->references('dni')->on('colaboradores');

            $table->unsignedSmallInteger('sucursal_Id');
            $table->foreign('sucursal_Id')->references('id')->on('sucursales');

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_salida');

            $table->smallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados');

            $table->string('descripcion');
            $table->string('comentario')->nullable();

            $table->unsignedSmallInteger('xnova_id');
            $table->foreign('xnova_id')->references('id')->on('xnovas');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
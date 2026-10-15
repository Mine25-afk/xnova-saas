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
        Schema::create('socio_negocios', function (Blueprint $table) {
            $table->string('dni')->primary()->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->string('telefono');
            $table->char('sexo');
            $table->char('tipo');
            $table->unsignedSmallInteger('xnova_id');
            $table->foreign('xnova_id')->references('id')->on('xnovas');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socio_negocio');
    }
};

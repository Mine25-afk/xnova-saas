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
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->string('dni')->primary();
            $table->foreign('dni')->references('dni')->on('socio_negocios')->cascadeOnDelete();
            $table->unsignedSmallInteger('puesto_id')->nullable();
            $table->foreign('puesto_id')->references('id')->on('puestos');
            $table->unsignedSmallInteger('xnova_id');
            $table->foreign('xnova_id')->references('id')->on('xnovas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};

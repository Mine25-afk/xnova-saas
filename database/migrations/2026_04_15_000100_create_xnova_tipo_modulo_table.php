<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xnova_tipo_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xnova_tipo_id')->constrained('xNova_Tipo')->cascadeOnDelete();
            $table->unsignedSmallInteger('modulo_id');
            $table->foreign('modulo_id')->references('id')->on('modulos')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['xnova_tipo_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xnova_tipo_modulo');
    }
};

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
        Schema::create('paquete_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paquete_id')->constrained('paquetes')->onDelete('cascade');
            $table->unsignedSmallInteger('modulo_id');
            $table->foreign('modulo_id')->references('id')->on('modulos')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['paquete_id', 'modulo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paquete_modulo');
    }
};

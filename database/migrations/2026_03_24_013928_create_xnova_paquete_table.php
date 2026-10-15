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
        Schema::create('xnova_paquete', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreign('xnova_id')->references('id')->on('xnovas')->onDelete('cascade');
            $table->foreignId('paquete_id')->constrained('paquetes')->onDelete('cascade');
            $table->decimal('precio_mensual', 12, 2); // precio pactado
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['xnova_id', 'paquete_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xnova_paquete');
    }
};

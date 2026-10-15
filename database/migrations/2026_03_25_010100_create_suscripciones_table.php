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
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xnova_paquete_id')->constrained('xnova_paquete')->cascadeOnDelete();
            $table->string('proveedor', 30)->default('paypal');
            $table->string('estado', 40)->default('pendiente');
            $table->string('plan_externo')->nullable();
            $table->string('referencia_externa')->nullable()->unique();
            $table->unsignedInteger('usuarios_incluidos')->default(3);
            $table->unsignedInteger('usuarios_extra')->default(0);
            $table->unsignedInteger('usuarios_totales')->default(3);
            $table->decimal('precio_base', 12, 2)->default(0);
            $table->decimal('precio_usuario_extra', 12, 2)->default(1.50);
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->string('moneda', 3)->default('USD');
            $table->timestamp('fecha_activacion')->nullable();
            $table->timestamp('periodo_inicio')->nullable();
            $table->timestamp('periodo_fin')->nullable();
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};

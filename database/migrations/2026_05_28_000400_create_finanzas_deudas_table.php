<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finanzas_deudas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->string('nombre', 140);
            $table->string('acreedor', 140)->nullable();
            $table->string('icono')->nullable();
            $table->string('categoria', 80)->nullable();
            $table->decimal('monto_original', 12, 2);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('notas', 240)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
            $table->index(['xnova_id', 'saldo_pendiente']);
        });

        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->foreignId('finanzas_deuda_id')->nullable()->after('finanzas_propietario_id')->constrained('finanzas_deudas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finanzas_deuda_id');
        });

        Schema::dropIfExists('finanzas_deudas');
    }
};

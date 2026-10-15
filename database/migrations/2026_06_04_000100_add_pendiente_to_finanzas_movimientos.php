<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            // Operación pendiente de confirmación (transferencia, pago de deuda, cobro).
            // Mientras está pendiente NO afecta saldos ni deudas/cuentas por cobrar.
            $table->boolean('pendiente')->default(false)->after('motivo_anulacion');
            $table->timestamp('confirmado_en')->nullable()->after('pendiente');
            $table->index(['xnova_id', 'pendiente']);
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->dropIndex(['xnova_id', 'pendiente']);
            $table->dropColumn(['pendiente', 'confirmado_en']);
        });
    }
};

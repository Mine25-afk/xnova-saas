<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->timestamp('anulado_en')->nullable()->after('fecha');
            $table->string('motivo_anulacion', 240)->nullable()->after('anulado_en');
            $table->decimal('monto_original', 18, 2)->nullable()->after('motivo_anulacion');
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->dropColumn(['anulado_en', 'motivo_anulacion', 'monto_original']);
        });
    }
};

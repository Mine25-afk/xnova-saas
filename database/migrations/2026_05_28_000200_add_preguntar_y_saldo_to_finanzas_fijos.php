<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_ingresos_fijos', function (Blueprint $table): void {
            $table->boolean('preguntar_al_usar')->default(false)->after('monto');
            $table->decimal('saldo_restante', 12, 2)->nullable()->after('preguntar_al_usar');
        });

        Schema::table('finanzas_gastos_fijos', function (Blueprint $table): void {
            $table->boolean('preguntar_al_usar')->default(false)->after('monto');
            $table->decimal('saldo_restante', 12, 2)->nullable()->after('preguntar_al_usar');
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_ingresos_fijos', function (Blueprint $table): void {
            $table->dropColumn(['preguntar_al_usar', 'saldo_restante']);
        });

        Schema::table('finanzas_gastos_fijos', function (Blueprint $table): void {
            $table->dropColumn(['preguntar_al_usar', 'saldo_restante']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_cuentas', function (Blueprint $table): void {
            $table->string('icono', 60)->nullable()->after('tipo');
        });

        Schema::table('finanzas_subcuentas', function (Blueprint $table): void {
            $table->string('icono', 60)->nullable()->after('nombre');
        });

        Schema::table('finanzas_ingresos_fijos', function (Blueprint $table): void {
            $table->string('icono', 60)->nullable()->after('nombre');
        });

        Schema::table('finanzas_gastos_fijos', function (Blueprint $table): void {
            $table->string('icono', 60)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_cuentas', function (Blueprint $table): void {
            $table->dropColumn('icono');
        });

        Schema::table('finanzas_subcuentas', function (Blueprint $table): void {
            $table->dropColumn('icono');
        });

        Schema::table('finanzas_ingresos_fijos', function (Blueprint $table): void {
            $table->dropColumn('icono');
        });

        Schema::table('finanzas_gastos_fijos', function (Blueprint $table): void {
            $table->dropColumn('icono');
        });
    }
};

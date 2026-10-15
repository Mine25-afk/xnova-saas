<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_cuentas', function (Blueprint $table): void {
            $table->dropUnique('finanzas_cuentas_xnova_id_nombre_unique');
            $table->unique(['xnova_id', 'finanzas_propietario_id', 'nombre'], 'finanzas_cuentas_xnova_propietario_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_cuentas', function (Blueprint $table): void {
            $table->dropUnique('finanzas_cuentas_xnova_propietario_nombre_unique');
            $table->unique(['xnova_id', 'nombre'], 'finanzas_cuentas_xnova_id_nombre_unique');
        });
    }
};

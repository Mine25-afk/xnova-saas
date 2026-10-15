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
        Schema::table('paquetes', function (Blueprint $table) {
            $table->unsignedInteger('sucursales_incluidas')
                ->default(1)
                ->after('precio_usuario_extra');
            $table->decimal('precio_sucursal_extra', 12, 2)
                ->default(3.00)
                ->after('sucursales_incluidas');
        });

        Schema::table('suscripciones', function (Blueprint $table) {
            $table->unsignedInteger('sucursales_incluidas')
                ->default(1)
                ->after('usuarios_totales');
            $table->unsignedInteger('sucursales_extra')
                ->default(0)
                ->after('sucursales_incluidas');
            $table->unsignedInteger('sucursales_totales')
                ->default(1)
                ->after('sucursales_extra');
            $table->decimal('precio_sucursal_extra', 12, 2)
                ->default(3.00)
                ->after('precio_usuario_extra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suscripciones', function (Blueprint $table) {
            $table->dropColumn([
                'sucursales_incluidas',
                'sucursales_extra',
                'sucursales_totales',
                'precio_sucursal_extra',
            ]);
        });

        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropColumn([
                'sucursales_incluidas',
                'precio_sucursal_extra',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['cliente_dni']);
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->string('cliente_dni')->nullable()->change();
            $table->foreign('cliente_dni')->references('dni')->on('socio_negocios');

            $table->foreignId('producto_id')->nullable()->after('sucursal_Id')->constrained('productos');
            $table->string('cliente_nombre_publico')->nullable()->after('descripcion');
            $table->string('cliente_identidad_publica', 25)->nullable()->after('cliente_nombre_publico');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'cliente_nombre_publico', 'cliente_identidad_publica']);

            $table->dropForeign(['cliente_dni']);
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->string('cliente_dni')->nullable(false)->change();
            $table->foreign('cliente_dni')->references('dni')->on('socio_negocios');
        });
    }
};

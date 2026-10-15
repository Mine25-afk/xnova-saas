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
            $table->text('descripcion')->nullable()->after('nombre');
            $table->unsignedInteger('usuarios_incluidos')->default(3)->after('precio');
            $table->decimal('precio_usuario_extra', 12, 2)->default(1.50)->after('usuarios_incluidos');
            $table->string('paypal_plan_id')->nullable()->after('precio_usuario_extra');
            $table->string('moneda', 3)->default('USD')->after('paypal_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paquetes', function (Blueprint $table) {
            $table->dropColumn([
                'descripcion',
                'usuarios_incluidos',
                'precio_usuario_extra',
                'paypal_plan_id',
                'moneda',
            ]);
        });
    }
};

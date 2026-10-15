<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('horarios_detalles', function (Blueprint $table) {
            $table->unsignedTinyInteger('dia')->nullable()->after('nombre');
        });

        DB::statement("
            UPDATE horarios_detalles
            SET dia = CASE LOWER(nombre)
                WHEN 'domingo' THEN 0
                WHEN 'lunes' THEN 1
                WHEN 'martes' THEN 2
                WHEN 'miercoles' THEN 3
                WHEN 'miércoles' THEN 3
                WHEN 'jueves' THEN 4
                WHEN 'viernes' THEN 5
                WHEN 'sabado' THEN 6
                WHEN 'sábado' THEN 6
                ELSE dia
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_detalles', function (Blueprint $table) {
            $table->dropColumn('dia');
        });
    }
};

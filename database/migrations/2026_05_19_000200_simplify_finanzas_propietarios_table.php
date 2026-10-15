<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finanzas_propietarios', function (Blueprint $table): void {
            if (Schema::hasColumn('finanzas_propietarios', 'tipo')) {
                $table->dropColumn('tipo');
            }

            if (Schema::hasColumn('finanzas_propietarios', 'correo')) {
                $table->dropColumn('correo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finanzas_propietarios', function (Blueprint $table): void {
            if (! Schema::hasColumn('finanzas_propietarios', 'tipo')) {
                $table->string('tipo', 40)->default('propietario')->after('nombre');
            }

            if (! Schema::hasColumn('finanzas_propietarios', 'correo')) {
                $table->string('correo')->nullable()->after('tipo');
            }
        });
    }
};

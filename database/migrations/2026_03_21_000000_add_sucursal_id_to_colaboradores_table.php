<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->unsignedSmallInteger('sucursal_Id')->nullable()->after('puesto_id');
            $table->foreign('sucursal_Id')->references('id')->on('sucursales');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropForeign(['sucursal_Id']);
            $table->dropColumn('sucursal_Id');
        });
    }
};

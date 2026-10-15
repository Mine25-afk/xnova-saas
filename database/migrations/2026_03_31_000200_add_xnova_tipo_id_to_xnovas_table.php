<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xnovas', function (Blueprint $table) {
            $table->foreignId('xnova_tipo_id')
                ->nullable()
                ->after('nombre')
                ->constrained('xNova_Tipo');
        });
    }

    public function down(): void
    {
        Schema::table('xnovas', function (Blueprint $table) {
            $table->dropForeign(['xnova_tipo_id']);
            $table->dropColumn('xnova_tipo_id');
        });
    }
};

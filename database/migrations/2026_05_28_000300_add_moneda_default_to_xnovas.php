<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xnovas', function (Blueprint $table): void {
            $table->string('moneda_default', 3)->default('LPS')->after('reservacion_uid');
        });
    }

    public function down(): void
    {
        Schema::table('xnovas', function (Blueprint $table): void {
            $table->dropColumn('moneda_default');
        });
    }
};

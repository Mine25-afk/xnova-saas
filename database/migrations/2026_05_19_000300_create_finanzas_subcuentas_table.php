<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finanzas_subcuentas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('xnova_id');
            $table->foreignId('finanzas_cuenta_id')->constrained('finanzas_cuentas')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->decimal('meta', 12, 2)->nullable();
            $table->boolean('es_general')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('xnova_id')->references('id')->on('xnovas')->cascadeOnDelete();
            $table->unique(['finanzas_cuenta_id', 'nombre']);
        });

        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->foreignId('finanzas_subcuenta_id')->nullable()->after('finanzas_cuenta_destino_id')->constrained('finanzas_subcuentas')->nullOnDelete();
            $table->foreignId('finanzas_subcuenta_destino_id')->nullable()->after('finanzas_subcuenta_id')->constrained('finanzas_subcuentas')->nullOnDelete();
        });

        $now = now();

        DB::table('finanzas_cuentas')
            ->select(['id', 'xnova_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($cuenta) use ($now): void {
                DB::table('finanzas_subcuentas')->updateOrInsert(
                    [
                        'finanzas_cuenta_id' => $cuenta->id,
                        'nombre' => 'General',
                    ],
                    [
                        'xnova_id' => $cuenta->xnova_id,
                        'meta' => null,
                        'es_general' => true,
                        'active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            });

        DB::table('finanzas_movimientos')
            ->select(['id', 'finanzas_cuenta_id', 'finanzas_cuenta_destino_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($movimiento): void {
                $origenGeneralId = DB::table('finanzas_subcuentas')
                    ->where('finanzas_cuenta_id', $movimiento->finanzas_cuenta_id)
                    ->where('es_general', true)
                    ->value('id');

                $destinoGeneralId = $movimiento->finanzas_cuenta_destino_id
                    ? DB::table('finanzas_subcuentas')
                        ->where('finanzas_cuenta_id', $movimiento->finanzas_cuenta_destino_id)
                        ->where('es_general', true)
                        ->value('id')
                    : null;

                DB::table('finanzas_movimientos')
                    ->where('id', $movimiento->id)
                    ->update([
                        'finanzas_subcuenta_id' => $origenGeneralId,
                        'finanzas_subcuenta_destino_id' => $destinoGeneralId,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('finanzas_movimientos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('finanzas_subcuenta_destino_id');
            $table->dropConstrainedForeignId('finanzas_subcuenta_id');
        });

        Schema::dropIfExists('finanzas_subcuentas');
    }
};

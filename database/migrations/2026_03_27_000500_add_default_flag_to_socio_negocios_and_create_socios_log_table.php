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
        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->boolean('es_por_defecto')->default(false)->after('active');
        });

        DB::table('socio_negocios')->update([
            'es_por_defecto' => false,
        ]);

        DB::table('xnovas')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($xnova): void {
                $ownerCollaboratorDni = DB::table('users')
                    ->where('id', $xnova->user_id)
                    ->where('xnova_id', $xnova->id)
                    ->value('colaborador_dni');

                $defaultEmployeeDni = $ownerCollaboratorDni ?: DB::table('socio_negocios')
                    ->where('xnova_id', $xnova->id)
                    ->where('tipo', 'E')
                    ->orderBy('created_at')
                    ->orderBy('dni')
                    ->value('dni');

                if ($defaultEmployeeDni) {
                    DB::table('socio_negocios')
                        ->where('dni', $defaultEmployeeDni)
                        ->where('xnova_id', $xnova->id)
                        ->update(['es_por_defecto' => true]);
                }

                $defaultClientDni = DB::table('socio_negocios')
                    ->where('xnova_id', $xnova->id)
                    ->where('tipo', 'C')
                    ->orderBy('created_at')
                    ->orderBy('dni')
                    ->value('dni');

                if ($defaultClientDni) {
                    DB::table('socio_negocios')
                        ->where('dni', $defaultClientDni)
                        ->where('xnova_id', $xnova->id)
                        ->update(['es_por_defecto' => true]);
                }
            });

        Schema::create('socios_log', function (Blueprint $table) {
            $table->id();
            $table->string('Socio_Dni');
            $table->foreign('Socio_Dni')->references('dni')->on('socio_negocios')->cascadeOnDelete();
            $table->string('Accion', 20);
            $table->timestamp('Fecha')->useCurrent();
            $table->foreignId('User_Modifica_Id')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['Socio_Dni', 'Accion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('socios_log');

        Schema::table('socio_negocios', function (Blueprint $table) {
            $table->dropColumn('es_por_defecto');
        });
    }
};

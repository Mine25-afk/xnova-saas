<?php

namespace Database\Seeders;

use App\Models\XnovaTipo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class XnovaTipoModuloSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $relations = [
            XnovaTipo::TIPO_CLINICA => ['SEGURIDAD', 'CONFIGURACION', 'SOCIOS', 'CITAS', 'INVENTARIO'],
            XnovaTipo::TIPO_SALONES => ['SEGURIDAD', 'CONFIGURACION', 'SOCIOS', 'CITAS', 'INVENTARIO'],
            XnovaTipo::TIPO_BARBERIAS => ['SEGURIDAD', 'CONFIGURACION', 'SOCIOS', 'CITAS', 'INVENTARIO'],
            XnovaTipo::TIPO_CATALOGOS => ['INVENTARIO'],
            XnovaTipo::TIPO_FINANZAS => ['SEGURIDAD', 'FINANZAS'],
        ];

        $tipoIdByName = DB::table('xNova_Tipo')
            ->pluck('id', 'tipo');

        $moduloIdByCode = DB::table('modulos')
            ->pluck('id', 'codigo');

        foreach ($relations as $tipoName => $moduleCodes) {
            $tipoId = $tipoIdByName[$tipoName] ?? null;

            if (! $tipoId) {
                continue;
            }

            $desiredModuleIds = collect($moduleCodes)
                ->map(fn ($moduleCode) => $moduloIdByCode[$moduleCode] ?? null)
                ->filter()
                ->values();

            DB::table('xnova_tipo_modulo')
                ->where('xnova_tipo_id', $tipoId)
                ->when($desiredModuleIds->isNotEmpty(), function ($query) use ($desiredModuleIds) {
                    $query->whereNotIn('modulo_id', $desiredModuleIds->all());
                })
                ->delete();

            foreach ($moduleCodes as $moduleCode) {
                $moduleId = $moduloIdByCode[$moduleCode] ?? null;

                if (! $moduleId) {
                    continue;
                }

                DB::table('xnova_tipo_modulo')->updateOrInsert(
                    [
                        'xnova_tipo_id' => $tipoId,
                        'modulo_id' => $moduleId,
                    ],
                    [
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }
}

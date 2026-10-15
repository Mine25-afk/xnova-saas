<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class XnovaTiposSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            'Clinica',
            'Salones',
            'Barberias',
            'Catalogos',
            'Finanzas Personales y en Pareja',
        ] as $tipo) {
            DB::table('xNova_Tipo')->updateOrInsert(
                ['tipo' => $tipo],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}

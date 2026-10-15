<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estados')->upsert([
            ['id' => 1, 'nombre' => 'Reservada'],
            ['id' => 2, 'nombre' => 'Terminada'],
            ['id' => 3, 'nombre' => 'Cancelada'],
            ['id' => 4, 'nombre' => 'Autorizado'],
            ['id' => 5, 'nombre' => 'Pendiente'],
        ], ['id'], ['nombre']);
    }
}

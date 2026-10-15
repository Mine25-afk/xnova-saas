<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            XnovaTiposSeeder::class,
            CatalogoAccesosSeeder::class,
            XnovaTipoModuloSeeder::class,
            EstadosSeeder::class,
            PaquetesSeeder::class,
        ]);
    }
}

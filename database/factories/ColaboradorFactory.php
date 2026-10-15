<?php

namespace Database\Factories;

use App\Models\Colaborador;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Requiere un socio tipo colaborador con el mismo dni (llave foránea).
 *
 * @extends Factory<Colaborador>
 */
class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('0801-####-#####'),
            'puesto_id' => null,
            'sucursal_Id' => null,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

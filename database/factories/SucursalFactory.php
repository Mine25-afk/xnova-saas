<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sucursal>
 */
class SucursalFactory extends Factory
{
    protected $model = Sucursal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Sucursal '.fake()->unique()->city(),
            'horario_id' => 0,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

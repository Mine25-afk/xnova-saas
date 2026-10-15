<?php

namespace Database\Factories;

use App\Models\FinanzasPropietario;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasPropietario>
 */
class FinanzasPropietarioFactory extends Factory
{
    protected $model = FinanzasPropietario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->firstName(),
            'active' => true,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

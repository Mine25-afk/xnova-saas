<?php

namespace Database\Factories;

use App\Models\Puesto;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Puesto>
 */
class PuestoFactory extends Factory
{
    protected $model = Puesto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->jobTitle(),
            'active' => true,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

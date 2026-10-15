<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Rol '.fake()->unique()->word(),
            'xnova_id' => Xnova::factory(),
        ];
    }
}

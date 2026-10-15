<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->words(2, true)),
            'descripcion' => fake()->sentence(),
            'precio' => fake()->randomFloat(2, 50, 1500),
            'active' => true,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

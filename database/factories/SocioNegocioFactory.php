<?php

namespace Database\Factories;

use App\Models\SocioNegocio;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocioNegocio>
 */
class SocioNegocioFactory extends Factory
{
    protected $model = SocioNegocio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('0801-####-#####'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'fecha_nacimiento' => fake()->date('Y-m-d', '-18 years'),
            'telefono' => fake()->numerify('9###-####'),
            'correo' => fake()->unique()->safeEmail(),
            'sexo' => fake()->randomElement(['M', 'F']),
            'tipo' => SocioNegocio::TIPO_CLIENTE,
            'xnova_id' => Xnova::factory(),
            'active' => true,
            'es_por_defecto' => false,
        ];
    }

    public function colaborador(): static
    {
        return $this->state(fn (array $attributes) => ['tipo' => SocioNegocio::TIPO_COLABORADOR]);
    }

    public function porDefecto(): static
    {
        return $this->state(fn (array $attributes) => ['es_por_defecto' => true]);
    }
}

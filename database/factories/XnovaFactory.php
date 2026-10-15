<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Xnova>
 */
class XnovaFactory extends Factory
{
    protected $model = Xnova::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'moneda_default' => 'LPS',
            'active' => true,
            'user_id' => User::factory(),
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }
}

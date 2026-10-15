<?php

namespace Database\Factories;

use App\Models\FinanzasDeuda;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasDeuda>
 */
class FinanzasDeudaFactory extends Factory
{
    protected $model = FinanzasDeuda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Préstamo '.fake()->unique()->word(),
            'acreedor' => fake()->company(),
            'monto_original' => 1000,
            'saldo_pendiente' => 1000,
            'fecha' => now()->toDateString(),
            'active' => true,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

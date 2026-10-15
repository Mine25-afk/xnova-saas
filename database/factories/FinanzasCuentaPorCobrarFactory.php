<?php

namespace Database\Factories;

use App\Models\FinanzasCuentaPorCobrar;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasCuentaPorCobrar>
 */
class FinanzasCuentaPorCobrarFactory extends Factory
{
    protected $model = FinanzasCuentaPorCobrar::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Factura '.fake()->unique()->numerify('####'),
            'deudor' => fake()->company(),
            'monto_original' => 500,
            'saldo_pendiente' => 500,
            'fecha' => now()->toDateString(),
            'active' => true,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

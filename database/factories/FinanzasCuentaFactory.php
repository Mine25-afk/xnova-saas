<?php

namespace Database\Factories;

use App\Models\FinanzasCuenta;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasCuenta>
 */
class FinanzasCuentaFactory extends Factory
{
    protected $model = FinanzasCuenta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Cuenta '.fake()->unique()->word(),
            'tipo' => 'banco',
            'moneda' => 'LPS',
            'active' => true,
            'finanzas_propietario_id' => null,
            'xnova_id' => Xnova::factory(),
        ];
    }
}

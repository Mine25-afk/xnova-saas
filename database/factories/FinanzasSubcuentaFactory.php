<?php

namespace Database\Factories;

use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasSubcuenta>
 */
class FinanzasSubcuentaFactory extends Factory
{
    protected $model = FinanzasSubcuenta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Bolsillo '.fake()->unique()->word(),
            'es_general' => false,
            'active' => true,
        ];
    }

    /**
     * Bolsillo de la cuenta indicada (misma empresa).
     */
    public function deCuenta(FinanzasCuenta $cuenta): static
    {
        return $this->state(fn (array $attributes) => [
            'finanzas_cuenta_id' => $cuenta->id,
            'xnova_id' => $cuenta->xnova_id,
        ]);
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => ['nombre' => 'General', 'es_general' => true]);
    }
}

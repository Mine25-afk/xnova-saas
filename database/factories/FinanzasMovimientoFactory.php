<?php

namespace Database\Factories;

use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanzasMovimiento>
 */
class FinanzasMovimientoFactory extends Factory
{
    protected $model = FinanzasMovimiento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => FinanzasMovimiento::TIPO_INGRESO,
            'descripcion' => fake()->sentence(3),
            'categoria' => null,
            'monto' => 100,
            'fecha' => now()->toDateString(),
            'pendiente' => false,
        ];
    }

    /**
     * Movimiento sobre un bolsillo (toma cuenta y empresa del bolsillo).
     */
    public function enBolsillo(FinanzasSubcuenta $bolsillo): static
    {
        return $this->state(fn (array $attributes) => [
            'finanzas_cuenta_id' => $bolsillo->finanzas_cuenta_id,
            'finanzas_subcuenta_id' => $bolsillo->id,
            'xnova_id' => $bolsillo->xnova_id,
        ]);
    }

    public function gasto(): static
    {
        return $this->state(fn (array $attributes) => ['tipo' => FinanzasMovimiento::TIPO_GASTO]);
    }
}

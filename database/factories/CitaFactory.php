<?php

namespace Database\Factories;

use App\Models\Cita;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Las llaves (cliente, colaborador, sucursal, empresa) se pasan siempre de
 * forma explícita para que la cita quede dentro de una sola empresa.
 *
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => now()->addDay()->toDateString(),
            'hora_inicio' => '09:00',
            'hora_salida' => '10:00',
            'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
            'descripcion' => fake()->sentence(3),
            'comentario' => null,
        ];
    }

    public function reservada(): static
    {
        return $this->state(fn (array $attributes) => ['estado_id' => Cita::ESTADO_RESERVADA_ID]);
    }
}

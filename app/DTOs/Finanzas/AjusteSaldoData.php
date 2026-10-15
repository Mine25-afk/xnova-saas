<?php

namespace App\DTOs\Finanzas;

/**
 * Ajuste del saldo real de una cuenta repartido entre bolsillos.
 */
final readonly class AjusteSaldoData
{
    /**
     * @param  array<int, float>  $asignaciones  subcuenta_id => monto (siempre positivo)
     */
    public function __construct(
        public float $saldoReal,
        public array $asignaciones,
    ) {}
}

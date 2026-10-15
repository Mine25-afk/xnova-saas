<?php

namespace App\DTOs\Finanzas;

/**
 * Abono a una deuda o cobro de una cuenta por cobrar desde un bolsillo.
 */
final readonly class PagoData
{
    public function __construct(
        public int $subcuentaId,
        public float $monto,
        public bool $pendiente = false,
    ) {}
}

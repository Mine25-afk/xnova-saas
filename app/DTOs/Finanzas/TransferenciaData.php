<?php

namespace App\DTOs\Finanzas;

/**
 * Transferencia desde un bolsillo hacia otro bolsillo o hacia una cuenta
 * (creando allí un bolsillo "espejo" con el mismo nombre).
 */
final readonly class TransferenciaData
{
    public function __construct(
        public float $monto,
        public ?int $subcuentaDestinoId = null,
        public bool $crearBolsilloEspejo = false,
        public ?int $cuentaDestinoId = null,
        public ?string $descripcion = null,
        public bool $pendiente = false,
    ) {}
}

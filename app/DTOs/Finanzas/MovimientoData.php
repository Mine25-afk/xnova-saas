<?php

namespace App\DTOs\Finanzas;

/**
 * Datos de un movimiento a registrar. Los ids ya vienen validados contra la
 * empresa activa por el Form Request.
 */
final readonly class MovimientoData
{
    public function __construct(
        public string $tipo,
        public int $cuentaId,
        public float $monto,
        public string $descripcion,
        public string $fecha,
        public ?int $subcuentaId = null,
        public ?int $cuentaDestinoId = null,
        public ?int $subcuentaDestinoId = null,
        public ?int $propietarioId = null,
        public ?string $categoria = null,
        public ?int $deudaId = null,
        public ?int $cuentaPorCobrarId = null,
        public bool $pendiente = false,
    ) {}
}

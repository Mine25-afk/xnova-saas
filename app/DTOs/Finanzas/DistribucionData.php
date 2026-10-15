<?php

namespace App\DTOs\Finanzas;

/**
 * Reparto de dinero de un bolsillo origen hacia otros bolsillos de la misma cuenta.
 */
final readonly class DistribucionData
{
    /**
     * @param  list<array{subcuenta_id: int, monto: float}>  $destinos
     */
    public function __construct(
        public int $origenSubcuentaId,
        public string $fecha,
        public array $destinos,
    ) {}

    /**
     * @return list<int>
     */
    public function destinoIds(): array
    {
        return array_map(fn (array $destino): int => $destino['subcuenta_id'], $this->destinos);
    }
}

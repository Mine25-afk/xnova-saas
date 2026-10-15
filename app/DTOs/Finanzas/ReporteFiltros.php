<?php

namespace App\DTOs\Finanzas;

use Carbon\Carbon;

/**
 * Filtros comunes de los reportes financieros.
 */
final readonly class ReporteFiltros
{
    public function __construct(
        public ?string $fechaInicio = null,
        public ?string $fechaFin = null,
        public ?int $cuentaId = null,
        public ?int $propietarioId = null,
        public ?string $tipo = null,
    ) {}

    public function rangoLabel(): string
    {
        $inicio = $this->fechaInicio !== null ? Carbon::parse($this->fechaInicio)->format('d/m/Y') : 'inicio';
        $fin = $this->fechaFin !== null ? Carbon::parse($this->fechaFin)->format('d/m/Y') : 'hoy';

        return "Periodo: {$inicio} — {$fin}";
    }

    /**
     * Parámetros de query string para reabrir el mismo reporte.
     *
     * @return array<string, int|string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'cuenta' => $this->cuentaId,
            'propietario' => $this->propietarioId,
            'tipo' => $this->tipo,
        ], fn (int|string|null $valor): bool => $valor !== null);
    }
}

<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\MovimientoRepository;
use Illuminate\Support\Collection;

/**
 * Operaciones marcadas como pendientes (transferencias, pagos de deuda y
 * cobros) presentadas como tarjetas para confirmarlas rápido.
 */
final class PendientesService
{
    public function __construct(private readonly MovimientoRepository $movimientos) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listado(): Collection
    {
        return $this->movimientos->pendientesDeConfirmar()
            ->map(fn (FinanzasMovimiento $m): array => $this->tarjeta($m))
            ->values()
            ->toBase();
    }

    /**
     * @return array<string, mixed>
     */
    private function tarjeta(FinanzasMovimiento $m): array
    {
        [$clase, $titulo, $destino] = match (true) {
            $m->finanzas_deuda_id !== null => ['deuda', 'Pago de deuda', $m->deuda?->nombre],
            $m->finanzas_cuenta_por_cobrar_id !== null => ['cobro', 'Cobro', $m->cuentaPorCobrar?->nombre],
            $m->tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA => [
                'transferencia',
                'Transferencia',
                trim(($m->cuentaDestino?->nombre ? $m->cuentaDestino->nombre.' · ' : '').($m->subcuentaDestino?->nombre ?? '')),
            ],
            default => [(string) $m->tipo, ucfirst((string) $m->tipo), null],
        };

        return [
            'id' => $m->id,
            'clase' => $clase,
            'titulo' => $titulo,
            'descripcion' => $m->descripcion,
            'monto' => (float) $m->monto,
            'moneda' => $m->cuenta?->moneda ?? 'LPS',
            'origen' => trim(($m->cuenta?->nombre ?? '').($m->subcuenta?->nombre ? ' · '.$m->subcuenta->nombre : '')),
            'destino' => $destino ?: '—',
            'fecha' => $m->fecha->format('d/m/Y'),
        ];
    }
}

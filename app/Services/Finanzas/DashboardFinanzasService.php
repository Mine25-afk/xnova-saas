<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasCuenta;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\IngresoFijoRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use Illuminate\Support\Collection;

/**
 * Indicadores del dashboard financiero: saldos, flujo del mes y tendencia.
 */
final class DashboardFinanzasService
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public function __construct(
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoRepository $movimientos,
        private readonly IngresoFijoRepository $ingresosFijos,
        private readonly GastoFijoRepository $gastosFijos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        $cuentas = $this->cuentas->todasConPropietario();
        $saldos = $this->movimientos->saldosPorCuenta($cuentas->pluck('id')->map(fn ($id): int => (int) $id)->all());

        $movimientosMes = $this->movimientos->entreFechas(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $ingresosMes = $this->sumarIngresos($movimientosMes);
        $gastosMes = $this->sumarGastos($movimientosMes);

        return [
            'cuentas' => $cuentas,
            'balances' => $saldos,
            'saldoTotal' => array_sum($saldos),
            'ingresosMes' => $ingresosMes,
            'gastosMes' => $gastosMes,
            'flujoNeto' => $ingresosMes - $gastosMes,
            'margenNeto' => $ingresosMes > 0 ? (($ingresosMes - $gastosMes) / $ingresosMes) * 100 : 0,
            'ingresosFijos' => $this->ingresosFijos->totalActivos(),
            'gastosFijos' => $this->gastosFijos->totalActivos(),
            'tendencia' => $this->tendencia(6),
            'gastosCategoria' => $this->gastosPorCategoria($movimientosMes),
            'saldoCuentas' => $cuentas->map(fn (FinanzasCuenta $cuenta): array => [
                'nombre' => $cuenta->nombre,
                'saldo' => $saldos[(int) $cuenta->id] ?? 0.0,
            ])->values()->all(),
            'movimientosRecientes' => $this->movimientos->recientes(8),
            'pendientesCount' => $this->movimientos->contarPendientes(),
        ];
    }

    /**
     * Ingresos contra gastos de los últimos meses (incluido el actual).
     *
     * @return list<array{mes: string, ingresos: float, gastos: float}>
     */
    public function tendencia(int $meses): array
    {
        $inicio = now()->copy()->startOfMonth()->subMonths($meses - 1);
        $porMes = $this->movimientos->entreFechas($inicio->toDateString())
            ->groupBy(fn (FinanzasMovimiento $movimiento): string => $movimiento->fecha->format('Y-m'));

        $tendencia = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $referencia = now()->copy()->startOfMonth()->subMonths($i);
            $delMes = $porMes->get($referencia->format('Y-m'), collect());

            $tendencia[] = [
                'mes' => self::MESES[$referencia->month - 1].' '.$referencia->format('y'),
                'ingresos' => $this->sumarIngresos($delMes),
                'gastos' => $this->sumarGastos($delMes),
            ];
        }

        return $tendencia;
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     * @return Collection<string, float>
     */
    private function gastosPorCategoria(Collection $movimientos): Collection
    {
        return $movimientos
            ->where('tipo', FinanzasMovimiento::TIPO_GASTO)
            ->groupBy(fn (FinanzasMovimiento $movimiento): string => filled($movimiento->categoria) ? (string) $movimiento->categoria : 'Sin categoría')
            ->map(fn (Collection $grupo): float => (float) $grupo->sum('monto'))
            ->sortDesc()
            ->take(8)
            ->toBase();
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     */
    private function sumarIngresos(Collection $movimientos): float
    {
        return (float) $movimientos
            ->whereIn('tipo', [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE])
            ->sum('monto');
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     */
    private function sumarGastos(Collection $movimientos): float
    {
        return (float) $movimientos->where('tipo', FinanzasMovimiento::TIPO_GASTO)->sum('monto');
    }
}

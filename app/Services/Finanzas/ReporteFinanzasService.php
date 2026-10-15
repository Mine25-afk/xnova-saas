<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\ReporteFiltros;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Catálogo y construcción de los reportes financieros (vista, CSV y PDF).
 */
final class ReporteFinanzasService
{
    public const FLUJO_CAJA = 'flujo-caja';

    public const GASTOS_CATEGORIA = 'gastos-categoria';

    public const LIBRO_MOVIMIENTOS = 'libro-movimientos';

    public function __construct(
        private readonly MovimientoRepository $movimientos,
        private readonly CuentaRepository $cuentas,
    ) {}

    /**
     * @return array<string, array{titulo: string, descripcion: string, icono: string, filtros: list<string>}>
     */
    public function definiciones(): array
    {
        return [
            self::FLUJO_CAJA => [
                'titulo' => 'Flujo de caja',
                'descripcion' => 'Ingresos contra gastos y flujo neto, mes a mes dentro del periodo.',
                'icono' => 'chart-bar',
                'filtros' => ['rango', 'cuenta', 'propietario'],
            ],
            self::GASTOS_CATEGORIA => [
                'titulo' => 'Gastos por categoría',
                'descripcion' => 'Desglose de en qué se va el dinero, para detectar dónde recortar.',
                'icono' => 'banknotes',
                'filtros' => ['rango', 'cuenta'],
            ],
            self::LIBRO_MOVIMIENTOS => [
                'titulo' => 'Libro de movimientos',
                'descripcion' => 'Detalle completo de cada movimiento, ideal para contabilidad y auditoría.',
                'icono' => 'book-open',
                'filtros' => ['rango', 'cuenta', 'tipo', 'propietario'],
            ],
        ];
    }

    public function existe(string $tipo): bool
    {
        return array_key_exists($tipo, $this->definiciones());
    }

    /**
     * @return array{titulo: string, subtitulo: string, columnas: list<string>, filas: list<list<string>>, totales: list<string>, moneda: string}
     */
    public function construir(string $tipo, ReporteFiltros $filtros): array
    {
        abort_unless($this->existe($tipo), 404);

        $moneda = $this->cuentas->activas()->first()?->moneda ?? 'LPS';
        $movimientos = $this->movimientos->paraReporte($filtros);
        $encabezado = [
            'titulo' => $this->definiciones()[$tipo]['titulo'],
            'subtitulo' => $filtros->rangoLabel(),
            'moneda' => (string) $moneda,
        ];

        return $encabezado + match ($tipo) {
            self::FLUJO_CAJA => $this->flujoCaja($movimientos, (string) $moneda),
            self::GASTOS_CATEGORIA => $this->gastosPorCategoria($movimientos, (string) $moneda),
            default => $this->libroMovimientos($movimientos, (string) $moneda),
        };
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     * @return array{columnas: list<string>, filas: list<list<string>>, totales: list<string>}
     */
    private function flujoCaja(Collection $movimientos, string $moneda): array
    {
        $filas = [];
        $totalIngresos = 0.0;
        $totalGastos = 0.0;

        $porMes = $movimientos->groupBy(fn (FinanzasMovimiento $m): string => $m->fecha->format('Y-m'));

        foreach ($porMes as $clave => $grupo) {
            $ingresos = (float) $grupo->whereIn('tipo', [FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE])->sum('monto');
            $gastos = (float) $grupo->where('tipo', FinanzasMovimiento::TIPO_GASTO)->sum('monto');
            $totalIngresos += $ingresos;
            $totalGastos += $gastos;

            $filas[] = [
                Carbon::parse($clave.'-01')->locale('es')->isoFormat('MMMM YYYY'),
                number_format($ingresos, 2),
                number_format($gastos, 2),
                number_format($ingresos - $gastos, 2),
            ];
        }

        return [
            'columnas' => ['Mes', "Ingresos ({$moneda})", "Gastos ({$moneda})", "Flujo neto ({$moneda})"],
            'filas' => $filas,
            'totales' => ['TOTAL', number_format($totalIngresos, 2), number_format($totalGastos, 2), number_format($totalIngresos - $totalGastos, 2)],
        ];
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     * @return array{columnas: list<string>, filas: list<list<string>>, totales: list<string>}
     */
    private function gastosPorCategoria(Collection $movimientos, string $moneda): array
    {
        $gastos = $movimientos->where('tipo', FinanzasMovimiento::TIPO_GASTO);
        $total = (float) $gastos->sum('monto');

        $porCategoria = $gastos
            ->groupBy(fn (FinanzasMovimiento $m): string => filled($m->categoria) ? (string) $m->categoria : 'Sin categoría')
            ->map(fn (Collection $grupo): float => (float) $grupo->sum('monto'))
            ->sortDesc();

        $filas = [];

        foreach ($porCategoria as $categoria => $monto) {
            $filas[] = [
                (string) $categoria,
                number_format($monto, 2),
                ($total > 0 ? number_format(($monto / $total) * 100, 1) : '0.0').'%',
            ];
        }

        return [
            'columnas' => ['Categoría', "Total ({$moneda})", '% del total'],
            'filas' => $filas,
            'totales' => ['TOTAL', number_format($total, 2), '100%'],
        ];
    }

    /**
     * @param  Collection<int, FinanzasMovimiento>  $movimientos
     * @return array{columnas: list<string>, filas: list<list<string>>, totales: list<string>}
     */
    private function libroMovimientos(Collection $movimientos, string $moneda): array
    {
        $filas = [];
        $neto = 0.0;

        foreach ($movimientos as $m) {
            $esGasto = $m->tipo === FinanzasMovimiento::TIPO_GASTO;
            $neto += $esGasto ? -((float) $m->monto) : (float) $m->monto;

            $filas[] = [
                $m->fecha->format('d/m/Y'),
                (string) $m->descripcion,
                FinanzasMovimiento::TIPOS[$m->tipo] ?? ucfirst((string) $m->tipo),
                $m->cuenta?->nombre ?? '—',
                $m->subcuenta?->nombre ?? '—',
                filled($m->categoria) ? (string) $m->categoria : '—',
                ($esGasto ? '-' : '').number_format((float) $m->monto, 2),
            ];
        }

        return [
            'columnas' => ['Fecha', 'Descripción', 'Tipo', 'Cuenta', 'Bolsillo', 'Categoría', "Monto ({$moneda})"],
            'filas' => $filas,
            'totales' => ['', '', '', '', '', 'Neto', number_format($neto, 2)],
        ];
    }
}

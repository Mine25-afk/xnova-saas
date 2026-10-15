<?php

namespace App\Repositories\Finanzas;

use App\DTOs\Finanzas\ReporteFiltros;
use App\Models\FinanzasMovimiento;
use App\Repositories\BaseRepository;
use App\Services\Finanzas\SaldoCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @extends BaseRepository<FinanzasMovimiento>
 */
final class MovimientoRepository extends BaseRepository
{
    private const RELACIONES_LISTADO = [
        'propietario:id,nombre',
        'cuenta:id,nombre,moneda',
        'cuentaDestino:id,nombre',
        'subcuenta:id,nombre',
        'subcuentaDestino:id,nombre',
    ];

    public function __construct(private readonly SaldoCalculator $calculator) {}

    protected function model(): string
    {
        return FinanzasMovimiento::class;
    }

    /**
     * @param  array<int, int>  $cuentaIds
     * @return array<int, float>
     */
    public function saldosPorCuenta(array $cuentaIds): array
    {
        return $this->saldos($cuentaIds, 'finanzas_cuenta_id', 'finanzas_cuenta_destino_id');
    }

    /**
     * @param  array<int, int>  $subcuentaIds
     * @return array<int, float>
     */
    public function saldosPorSubcuenta(array $subcuentaIds): array
    {
        return $this->saldos($subcuentaIds, 'finanzas_subcuenta_id', 'finanzas_subcuenta_destino_id');
    }

    public function saldoCuenta(int $cuentaId): float
    {
        return $this->saldosPorCuenta([$cuentaId])[$cuentaId] ?? 0.0;
    }

    public function saldoSubcuenta(int $subcuentaId): float
    {
        return $this->saldosPorSubcuenta([$subcuentaId])[$subcuentaId] ?? 0.0;
    }

    /**
     * @return Builder<FinanzasMovimiento>
     */
    public function buscar(string $search = ''): Builder
    {
        return $this->query()
            ->with(self::RELACIONES_LISTADO)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhereHas('propietario', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('cuenta', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('cuentaDestino', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function entreFechas(string $desde, ?string $hasta = null): Collection
    {
        return $this->query()
            ->whereDate('fecha', '>=', $desde)
            ->when($hasta !== null, fn (Builder $query) => $query->whereDate('fecha', '<=', $hasta))
            ->get();
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function recientes(int $limite = 8): Collection
    {
        return $this->query()
            ->with(['cuenta:id,nombre', 'cuentaDestino:id,nombre', 'propietario:id,nombre'])
            ->aplicados()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function recientesDeCuenta(int $cuentaId, int $limite = 8): Collection
    {
        return $this->query()
            ->with(['subcuenta:id,nombre', 'subcuentaDestino:id,nombre'])
            ->where(function (Builder $query) use ($cuentaId): void {
                $query->where('finanzas_cuenta_id', $cuentaId)
                    ->orWhere('finanzas_cuenta_destino_id', $cuentaId);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function recientesDeSubcuenta(int $subcuentaId, int $limite = 15): Collection
    {
        return $this->query()
            ->with(['subcuenta:id,nombre', 'subcuentaDestino:id,nombre'])
            ->where(function (Builder $query) use ($subcuentaId): void {
                $query->where('finanzas_subcuenta_id', $subcuentaId)
                    ->orWhere('finanzas_subcuenta_destino_id', $subcuentaId);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function pendientesDeConfirmar(): Collection
    {
        return $this->query()
            ->with([
                'cuenta:id,nombre,moneda',
                'cuentaDestino:id,nombre',
                'subcuenta:id,nombre',
                'subcuentaDestino:id,nombre',
                'deuda:id,nombre',
                'cuentaPorCobrar:id,nombre',
            ])
            ->pendientesDeConfirmar()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
    }

    public function contarPendientes(): int
    {
        return $this->query()->pendientesDeConfirmar()->count();
    }

    public function contarDePropietario(int $propietarioId): int
    {
        return $this->query()->where('finanzas_propietario_id', $propietarioId)->count();
    }

    /**
     * @return SupportCollection<int, string>
     */
    public function categoriasUsadas(): SupportCollection
    {
        return $this->query()
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');
    }

    /**
     * Gastos e ingresos más repetidos en los últimos días, para registrarlos de nuevo con un toque.
     *
     * @return SupportCollection<int, array{descripcion: string, tipo: string, categoria: string|null, finanzas_cuenta_id: int|null, monto: float}>
     */
    public function favoritos(int $dias = 120, int $limite = 6): SupportCollection
    {
        return $this->query()
            ->whereIn('tipo', [FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_INGRESO])
            ->noAnulados()
            ->whereDate('fecha', '>=', now()->subDays($dias)->toDateString())
            ->selectRaw('descripcion, tipo, MAX(categoria) as categoria, MAX(finanzas_cuenta_id) as finanzas_cuenta_id, MAX(monto) as monto, COUNT(*) as veces')
            ->groupBy('descripcion', 'tipo')
            ->orderByDesc('veces')
            ->limit($limite)
            ->get()
            ->map(fn (FinanzasMovimiento $favorito): array => [
                'descripcion' => (string) $favorito->descripcion,
                'tipo' => (string) $favorito->tipo,
                'categoria' => $favorito->categoria,
                'finanzas_cuenta_id' => $favorito->finanzas_cuenta_id !== null ? (int) $favorito->finanzas_cuenta_id : null,
                'monto' => (float) $favorito->monto,
            ])
            ->values()
            ->toBase();
    }

    public function ultimoGastoOIngreso(): ?FinanzasMovimiento
    {
        return $this->query()
            ->whereIn('tipo', [FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_INGRESO])
            ->noAnulados()
            ->orderByDesc('id')
            ->first(['finanzas_cuenta_id', 'finanzas_subcuenta_id']);
    }

    /**
     * @return Collection<int, FinanzasMovimiento>
     */
    public function paraReporte(ReporteFiltros $filtros): Collection
    {
        return $this->query()
            ->with(['cuenta:id,nombre', 'cuentaDestino:id,nombre', 'subcuenta:id,nombre', 'propietario:id,nombre'])
            ->noAnulados()
            ->when($filtros->fechaInicio !== null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros->fechaInicio))
            ->when($filtros->fechaFin !== null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros->fechaFin))
            ->when($filtros->cuentaId !== null, function (Builder $query) use ($filtros): void {
                $query->where(function (Builder $q) use ($filtros): void {
                    $q->where('finanzas_cuenta_id', $filtros->cuentaId)
                        ->orWhere('finanzas_cuenta_destino_id', $filtros->cuentaId);
                });
            })
            ->when($filtros->propietarioId !== null, fn (Builder $q) => $q->where('finanzas_propietario_id', $filtros->propietarioId))
            ->when($filtros->tipo !== null, fn (Builder $q) => $q->where('tipo', $filtros->tipo))
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, float>
     */
    private function saldos(array $ids, string $columnaOrigen, string $columnaDestino): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $movimientos = $this->query()
            ->aplicados()
            ->where(function (Builder $query) use ($ids, $columnaOrigen, $columnaDestino): void {
                $query->whereIn($columnaOrigen, $ids)->orWhereIn($columnaDestino, $ids);
            })
            ->get(['tipo', 'monto', $columnaOrigen, $columnaDestino])
            ->map(fn (FinanzasMovimiento $movimiento): array => [
                'tipo' => (string) $movimiento->tipo,
                'monto' => (string) $movimiento->monto,
                'origen' => $this->entero($movimiento->getAttribute($columnaOrigen)),
                'destino' => $this->entero($movimiento->getAttribute($columnaDestino)),
            ]);

        return $this->calculator->calcular($movimientos, $ids);
    }

    private function entero(mixed $valor): ?int
    {
        return $valor === null ? null : (int) $valor;
    }
}

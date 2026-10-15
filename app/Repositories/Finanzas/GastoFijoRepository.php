<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasGastoFijo;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @extends BaseRepository<FinanzasGastoFijo>
 */
final class GastoFijoRepository extends BaseRepository
{
    protected function model(): string
    {
        return FinanzasGastoFijo::class;
    }

    /**
     * @return Builder<FinanzasGastoFijo>
     */
    public function buscar(string $search = '', ?int $propietarioId = null): Builder
    {
        return $this->query()
            ->with('propietario:id,nombre')
            ->when($propietarioId !== null, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('propietario', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * Activos del propietario indicado más los generales (sin propietario).
     *
     * @return Collection<int, FinanzasGastoFijo>
     */
    public function activosPara(?int $propietarioId): Collection
    {
        return $this->query()
            ->where('active', true)
            ->when($propietarioId !== null, function (Builder $query) use ($propietarioId): void {
                $query->where(fn (Builder $q) => $q->where('finanzas_propietario_id', $propietarioId)->orWhereNull('finanzas_propietario_id'));
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono', 'monto', 'preguntar_al_usar', 'saldo_restante']);
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
            ->pluck('categoria');
    }

    public function totalActivos(): float
    {
        return (float) $this->query()->where('active', true)->sum('monto');
    }

    public function contarDePropietario(int $propietarioId): int
    {
        return $this->query()->where('finanzas_propietario_id', $propietarioId)->count();
    }
}

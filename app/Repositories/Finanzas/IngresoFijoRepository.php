<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasIngresoFijo;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<FinanzasIngresoFijo>
 */
class IngresoFijoRepository extends BaseRepository
{
    protected function model(): string
    {
        return FinanzasIngresoFijo::class;
    }

    /**
     * @return Builder<FinanzasIngresoFijo>
     */
    public function buscar(string $search = '', ?int $propietarioId = null): Builder
    {
        return $this->query()
            ->with(['propietario:id,nombre', 'cuenta:id,nombre'])
            ->when($propietarioId !== null, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('propietario', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('cuenta', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * Activos del propietario indicado más los generales (sin propietario).
     *
     * @return Collection<int, FinanzasIngresoFijo>
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

    public function totalActivos(): float
    {
        return (float) $this->query()->where('active', true)->sum('monto');
    }

    public function contarDePropietario(int $propietarioId): int
    {
        return $this->query()->where('finanzas_propietario_id', $propietarioId)->count();
    }
}

<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasCuenta;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<FinanzasCuenta>
 */
class CuentaRepository extends BaseRepository
{
    protected function model(): string
    {
        return FinanzasCuenta::class;
    }

    /**
     * @return Builder<FinanzasCuenta>
     */
    public function buscar(string $search = '', ?int $propietarioId = null): Builder
    {
        return $this->query()
            ->with('propietario:id,nombre')
            ->when($propietarioId !== null, fn (Builder $query) => $query->where('finanzas_propietario_id', $propietarioId))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('tipo', 'like', "%{$search}%")
                        ->orWhere('moneda', 'like', "%{$search}%")
                        ->orWhereHas('propietario', fn (Builder $q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            });
    }

    /**
     * @return Collection<int, FinanzasCuenta>
     */
    public function todasConPropietario(): Collection
    {
        return $this->query()
            ->with('propietario:id,nombre')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * @return Collection<int, FinanzasCuenta>
     */
    public function activas(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'finanzas_propietario_id', 'tipo', 'moneda']);
    }

    /**
     * Cuentas activas con sus bolsillos activos (General primero).
     *
     * @return Collection<int, FinanzasCuenta>
     */
    public function activasConBolsillos(?int $excluirSubcuentaId = null): Collection
    {
        return $this->query()
            ->where('active', true)
            ->with([
                'propietario:id,nombre',
                'subcuentas' => function ($query) use ($excluirSubcuentaId): void {
                    $query->where('active', true)
                        ->when($excluirSubcuentaId !== null, fn ($q) => $q->where('id', '!=', $excluirSubcuentaId))
                        ->orderByDesc('es_general')
                        ->orderBy('nombre');
                },
            ])
            ->orderBy('nombre')
            ->get();
    }

    public function contarDePropietario(int $propietarioId): int
    {
        return $this->query()->where('finanzas_propietario_id', $propietarioId)->count();
    }
}

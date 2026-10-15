<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasPropietario;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<FinanzasPropietario>
 */
final class PropietarioRepository extends BaseRepository
{
    protected function model(): string
    {
        return FinanzasPropietario::class;
    }

    /**
     * @return Builder<FinanzasPropietario>
     */
    public function buscar(string $search = ''): Builder
    {
        return $this->query()
            ->select(['id', 'nombre', 'created_at'])
            ->withCount('cuentas')
            ->when($search !== '', fn (Builder $query) => $query->where('nombre', 'like', "%{$search}%"));
    }

    /**
     * @return Collection<int, FinanzasPropietario>
     */
    public function activos(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    public function total(): int
    {
        return $this->query()->count();
    }
}

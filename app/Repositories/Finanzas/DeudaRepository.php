<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasDeuda;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<FinanzasDeuda>
 */
class DeudaRepository extends BaseRepository
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADA = 'pagada';

    protected function model(): string
    {
        return FinanzasDeuda::class;
    }

    /**
     * @return Builder<FinanzasDeuda>
     */
    public function buscar(string $search = '', ?string $estado = null): Builder
    {
        return $this->query()
            ->when($estado === self::ESTADO_PENDIENTE, fn (Builder $query) => $query->where('saldo_pendiente', '>', 0))
            ->when($estado === self::ESTADO_PAGADA, fn (Builder $query) => $query->where('saldo_pendiente', '<=', 0))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('acreedor', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            });
    }

    /**
     * @return Collection<int, FinanzasDeuda>
     */
    public function activasConSaldo(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono', 'saldo_pendiente', 'fecha_vencimiento']);
    }

    public function totalPendiente(string $search = ''): float
    {
        return (float) $this->buscar($search, self::ESTADO_PENDIENTE)->sum('saldo_pendiente');
    }
}

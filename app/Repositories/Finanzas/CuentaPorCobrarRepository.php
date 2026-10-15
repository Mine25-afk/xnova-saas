<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasCuentaPorCobrar;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends BaseRepository<FinanzasCuentaPorCobrar>
 */
class CuentaPorCobrarRepository extends BaseRepository
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_COBRADA = 'cobrada';

    protected function model(): string
    {
        return FinanzasCuentaPorCobrar::class;
    }

    /**
     * @return Builder<FinanzasCuentaPorCobrar>
     */
    public function buscar(string $search = '', ?string $estado = null): Builder
    {
        return $this->query()
            ->when($estado === self::ESTADO_PENDIENTE, fn (Builder $query) => $query->where('saldo_pendiente', '>', 0))
            ->when($estado === self::ESTADO_COBRADA, fn (Builder $query) => $query->where('saldo_pendiente', '<=', 0))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('deudor', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            });
    }

    public function totalPendiente(string $search = ''): float
    {
        return (float) $this->buscar($search, self::ESTADO_PENDIENTE)->sum('saldo_pendiente');
    }
}

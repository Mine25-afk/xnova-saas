<?php

namespace App\Repositories\Finanzas;

use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<FinanzasSubcuenta>
 */
final class SubcuentaRepository extends BaseRepository
{
    protected function model(): string
    {
        return FinanzasSubcuenta::class;
    }

    /**
     * Bolsillo "General" de la cuenta; se crea si todavía no existe.
     */
    public function general(FinanzasCuenta $cuenta): FinanzasSubcuenta
    {
        return $this->query()->firstOrCreate(
            [
                'finanzas_cuenta_id' => $cuenta->id,
                'es_general' => true,
            ],
            [
                'xnova_id' => $cuenta->xnova_id,
                'nombre' => 'General',
                'meta' => null,
                'active' => true,
            ],
        );
    }

    /**
     * Devuelve el bolsillo pedido si es de la cuenta; si no, el General.
     */
    public function resolverParaCuenta(FinanzasCuenta $cuenta, ?int $subcuentaId): int
    {
        if ($subcuentaId !== null && $subcuentaId > 0) {
            $pertenece = $this->query()
                ->where('finanzas_cuenta_id', $cuenta->id)
                ->whereKey($subcuentaId)
                ->exists();

            if ($pertenece) {
                return $subcuentaId;
            }
        }

        return (int) $this->general($cuenta)->id;
    }

    public function deCuentaPorNombre(int $cuentaId, string $nombre): ?FinanzasSubcuenta
    {
        return $this->query()
            ->where('finanzas_cuenta_id', $cuentaId)
            ->where('nombre', $nombre)
            ->first();
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, FinanzasSubcuenta>
     */
    public function deCuenta(int $cuentaId, array $ids): Collection
    {
        return $this->query()
            ->where('finanzas_cuenta_id', $cuentaId)
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * @return Collection<int, FinanzasSubcuenta>
     */
    public function activas(): Collection
    {
        return $this->query()
            ->with('cuenta:id,nombre')
            ->where('active', true)
            ->orderBy('finanzas_cuenta_id')
            ->orderByDesc('es_general')
            ->orderBy('nombre')
            ->get(['id', 'finanzas_cuenta_id', 'nombre', 'es_general']);
    }
}

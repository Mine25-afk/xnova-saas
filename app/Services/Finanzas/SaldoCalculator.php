<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasMovimiento;

/**
 * Calcula saldos a partir de movimientos. Es lógica pura (sin base de datos)
 * para poder probarla de forma unitaria.
 *
 * Reglas: ingreso y ajuste suman al origen (un ajuste puede ser negativo),
 * gasto resta al origen y transferencia resta al origen y suma al destino.
 */
final class SaldoCalculator
{
    /**
     * @param  iterable<array{tipo: string, monto: float|int|string, origen: int|null, destino: int|null}>  $movimientos
     * @param  array<int, int>  $ids
     * @return array<int, float>
     */
    public function calcular(iterable $movimientos, array $ids): array
    {
        $saldos = array_fill_keys($ids, 0.0);

        foreach ($movimientos as $movimiento) {
            $monto = (float) $movimiento['monto'];
            $origen = $movimiento['origen'];
            $destino = $movimiento['destino'];

            $delta = match ($movimiento['tipo']) {
                FinanzasMovimiento::TIPO_INGRESO, FinanzasMovimiento::TIPO_AJUSTE => $monto,
                FinanzasMovimiento::TIPO_GASTO, FinanzasMovimiento::TIPO_TRANSFERENCIA => -$monto,
                default => 0.0,
            };

            if ($origen !== null && array_key_exists($origen, $saldos)) {
                $saldos[$origen] += $delta;
            }

            if ($movimiento['tipo'] === FinanzasMovimiento::TIPO_TRANSFERENCIA && $destino !== null && array_key_exists($destino, $saldos)) {
                $saldos[$destino] += $monto;
            }
        }

        return array_map(fn (float $saldo): float => round($saldo, 2), $saldos);
    }
}

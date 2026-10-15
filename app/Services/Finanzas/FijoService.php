<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasGastoFijo;
use App\Models\FinanzasIngresoFijo;

/**
 * Ingresos y gastos fijos "por pagos": si preguntar_al_usar está activo, cada
 * uso descuenta de saldo_restante hasta agotarlo y entonces se reinicia.
 */
final class FijoService
{
    /**
     * Saldo restante con el que nace un fijo.
     */
    public function saldoInicial(bool $preguntarAlUsar, float $monto): ?float
    {
        return $preguntarAlUsar ? $monto : null;
    }

    /**
     * Saldo restante tras editar un fijo: se inicializa al activar el modo por
     * pagos, se conserva si ya estaba activo y se limpia al desactivarlo.
     *
     * @return array{saldo_restante?: float|null}
     */
    public function saldoAlActualizar(FinanzasIngresoFijo|FinanzasGastoFijo $fijo, bool $preguntarAlUsar, float $monto): array
    {
        if (! $preguntarAlUsar) {
            return ['saldo_restante' => null];
        }

        if (! $fijo->preguntar_al_usar) {
            return ['saldo_restante' => $monto];
        }

        return [];
    }

    /**
     * Descuenta un uso del fijo. Si el saldo llega a cero, vuelve al monto original.
     *
     * @return array{saldo_restante?: float, reiniciado?: bool}
     */
    public function descontarUso(FinanzasIngresoFijo|FinanzasGastoFijo|null $fijo, float $montoUsado): array
    {
        if ($fijo === null || ! $fijo->preguntar_al_usar) {
            return [];
        }

        $saldoActual = $fijo->saldo_restante !== null ? (float) $fijo->saldo_restante : (float) $fijo->monto;
        $nuevoSaldo = $saldoActual - $montoUsado;
        $reiniciado = false;

        if ($nuevoSaldo <= 0) {
            $nuevoSaldo = (float) $fijo->monto;
            $reiniciado = true;
        }

        $fijo->update(['saldo_restante' => $nuevoSaldo]);

        return [
            'saldo_restante' => $nuevoSaldo,
            'reiniciado' => $reiniciado,
        ];
    }
}

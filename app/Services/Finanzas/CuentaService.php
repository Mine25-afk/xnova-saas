<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\AjusteSaldoData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use Illuminate\Support\Collection;

/**
 * Alta de cuentas (con su bolsillo General y saldo inicial) y ajuste de saldo real.
 */
final class CuentaService
{
    public function __construct(
        private readonly CuentaRepository $cuentas,
        private readonly SubcuentaRepository $subcuentas,
        private readonly MovimientoRepository $movimientos,
    ) {}

    /**
     * @param  array<string, mixed>  $atributos
     */
    public function crear(array $atributos, float $saldoInicial = 0.0): FinanzasCuenta
    {
        return $this->cuentas->transaction(function () use ($atributos, $saldoInicial): FinanzasCuenta {
            $cuenta = $this->cuentas->create($atributos);
            $general = $this->subcuentas->general($cuenta);

            if ($saldoInicial !== 0.0) {
                $this->movimientos->create([
                    'finanzas_cuenta_id' => $cuenta->id,
                    'finanzas_subcuenta_id' => $general->id,
                    'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                    'tipo' => $saldoInicial > 0 ? FinanzasMovimiento::TIPO_AJUSTE : FinanzasMovimiento::TIPO_GASTO,
                    'descripcion' => $saldoInicial > 0 ? 'Saldo inicial' : 'Saldo inicial negativo',
                    'categoria' => 'Saldo inicial',
                    'monto' => abs($saldoInicial),
                    'fecha' => now()->toDateString(),
                ]);
            }

            return $cuenta;
        });
    }

    /**
     * Ajusta la cuenta a su saldo real repartiendo la diferencia entre bolsillos.
     *
     * La diferencia (saldo real - saldo actual) puede ser positiva o negativa; los
     * montos asignados llegan como magnitudes positivas y su suma no puede
     * exceder la diferencia. Devuelve false si no había nada que ajustar.
     */
    public function ajustarSaldo(FinanzasCuenta $cuenta, AjusteSaldoData $data): bool
    {
        $saldoActual = $this->movimientos->saldoCuenta((int) $cuenta->id);
        $diferencia = round($data->saldoReal - $saldoActual, 2);

        if (abs($diferencia) < 0.01) {
            return false;
        }

        $asignaciones = array_filter(
            array_map(fn (float $monto): float => round($monto, 2), $data->asignaciones),
            fn (float $monto): bool => $monto > 0,
        );

        if ($asignaciones === []) {
            throw BusinessRuleException::enCampo('ajustes', 'Asigna al menos un monto a algun bolsillo.');
        }

        $totalAsignado = round(array_sum($asignaciones), 2);

        if ($totalAsignado > abs($diferencia) + 0.001) {
            throw BusinessRuleException::enCampo(
                'ajustes',
                'La suma asignada ('.number_format($totalAsignado, 2).') excede la diferencia de '.number_format(abs($diferencia), 2).'.',
            );
        }

        $signo = $diferencia > 0 ? 1 : -1;

        if ($signo < 0) {
            $saldosBolsillos = $this->movimientos->saldosPorSubcuenta(array_keys($asignaciones));

            foreach ($asignaciones as $subcuentaId => $monto) {
                if ($monto > ($saldosBolsillos[$subcuentaId] ?? 0) + 0.001) {
                    throw BusinessRuleException::enCampo('ajustes', 'No puedes descontar mas de lo que tiene un bolsillo. Revisa los montos.');
                }
            }
        }

        $this->movimientos->transaction(function () use ($cuenta, $asignaciones, $signo): void {
            foreach ($asignaciones as $subcuentaId => $monto) {
                $this->movimientos->create([
                    'finanzas_cuenta_id' => $cuenta->id,
                    'finanzas_subcuenta_id' => $subcuentaId,
                    'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                    'tipo' => FinanzasMovimiento::TIPO_AJUSTE,
                    'descripcion' => $signo > 0 ? 'Ajuste de saldo (suma)' : 'Ajuste de saldo (resta)',
                    'categoria' => 'Ajuste',
                    'monto' => $signo * $monto,
                    'fecha' => now()->toDateString(),
                ]);
            }
        });

        return true;
    }

    /**
     * Totales por moneda de un conjunto de cuentas, ya formateados para la vista.
     *
     * @param  Collection<int, FinanzasCuenta>  $cuentas
     * @return list<array{moneda: string, monto: float, label: string}>
     */
    public function totalesPorMoneda(Collection $cuentas): array
    {
        $saldos = $this->movimientos->saldosPorCuenta($cuentas->pluck('id')->map(fn ($id): int => (int) $id)->all());
        $totales = [];

        foreach ($cuentas as $cuenta) {
            $moneda = $cuenta->moneda ?: 'LPS';
            $totales[$moneda] = ($totales[$moneda] ?? 0) + ($saldos[(int) $cuenta->id] ?? 0);
        }

        ksort($totales);

        $resultado = [];

        foreach ($totales as $moneda => $monto) {
            $resultado[] = ['moneda' => (string) $moneda, 'monto' => (float) $monto, 'label' => $moneda.' '.number_format($monto, 2)];
        }

        return $resultado;
    }
}

<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\MovimientoData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasCuentaPorCobrar;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\CuentaPorCobrarRepository;
use App\Repositories\Finanzas\CuentaRepository;

/**
 * Alta de cuentas por cobrar y registro de cobros hacia un bolsillo.
 */
final class CuentaPorCobrarService
{
    public function __construct(
        private readonly CuentaPorCobrarRepository $cuentasPorCobrar,
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoService $movimientoService,
    ) {}

    /**
     * @param  array<string, mixed>  $atributos
     */
    public function crear(array $atributos): FinanzasCuentaPorCobrar
    {
        $atributos['saldo_pendiente'] = (float) $atributos['monto_original'];

        return $this->cuentasPorCobrar->create($atributos);
    }

    /**
     * Conserva lo ya cobrado: el saldo pendiente se recalcula con el nuevo monto total.
     *
     * @param  array<string, mixed>  $atributos
     */
    public function actualizar(FinanzasCuentaPorCobrar $cuentaPorCobrar, array $atributos): FinanzasCuentaPorCobrar
    {
        $cobrado = max(0, (float) $cuentaPorCobrar->monto_original - (float) $cuentaPorCobrar->saldo_pendiente);
        $atributos['saldo_pendiente'] = max(0, (float) $atributos['monto_original'] - $cobrado);

        return $this->cuentasPorCobrar->update($cuentaPorCobrar, $atributos);
    }

    /**
     * Registra un cobro como ingreso en el bolsillo elegido.
     *
     * @return array{message: string, saldo_pendiente: float, pendiente?: bool, cobrada?: bool}
     */
    public function registrarCobro(FinanzasCuentaPorCobrar $cuentaPorCobrar, FinanzasSubcuenta $bolsillo, float $monto, bool $pendiente = false): array
    {
        $monto = round($monto, 2);
        $saldoPendiente = (float) $cuentaPorCobrar->saldo_pendiente;

        if ($monto > $saldoPendiente) {
            throw new BusinessRuleException('El cobro supera el saldo pendiente.', extra: ['max' => $saldoPendiente]);
        }

        /** @var FinanzasCuenta $cuenta */
        $cuenta = $this->cuentas->findOrFail((int) $bolsillo->finanzas_cuenta_id);

        $this->cuentasPorCobrar->transaction(function () use ($cuentaPorCobrar, $cuenta, $bolsillo, $monto, $pendiente): void {
            $this->movimientoService->registrar(new MovimientoData(
                tipo: FinanzasMovimiento::TIPO_INGRESO,
                cuentaId: (int) $cuenta->id,
                monto: $monto,
                descripcion: 'Cobro: '.$cuentaPorCobrar->nombre,
                fecha: now()->toDateString(),
                subcuentaId: (int) $bolsillo->id,
                propietarioId: $cuenta->finanzas_propietario_id !== null ? (int) $cuenta->finanzas_propietario_id : null,
                categoria: 'Cobros',
                cuentaPorCobrarId: (int) $cuentaPorCobrar->id,
                pendiente: $pendiente,
            ));

            if (! $pendiente) {
                $cuentaPorCobrar->decrement('saldo_pendiente', $monto);
            }
        });

        if ($pendiente) {
            return ['message' => 'Cobro dejado como pendiente.', 'pendiente' => true, 'saldo_pendiente' => $saldoPendiente];
        }

        $saldoFinal = (float) $cuentaPorCobrar->refresh()->saldo_pendiente;

        return [
            'message' => $saldoFinal <= 0 ? 'Cuenta cobrada por completo.' : 'Cobro registrado.',
            'saldo_pendiente' => $saldoFinal,
            'cobrada' => $saldoFinal <= 0,
        ];
    }
}

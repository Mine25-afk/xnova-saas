<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\MovimientoData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasDeuda;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\DeudaRepository;
use App\Repositories\Finanzas\MovimientoRepository;

/**
 * Alta de deudas y registro de abonos desde un bolsillo.
 */
final class DeudaService
{
    public function __construct(
        private readonly DeudaRepository $deudas,
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoRepository $movimientos,
        private readonly MovimientoService $movimientoService,
    ) {}

    /**
     * @param  array<string, mixed>  $atributos
     */
    public function crear(array $atributos): FinanzasDeuda
    {
        $atributos['saldo_pendiente'] = (float) $atributos['monto_original'];

        return $this->deudas->create($atributos);
    }

    /**
     * Conserva lo ya pagado: el saldo pendiente se recalcula con el nuevo monto total.
     *
     * @param  array<string, mixed>  $atributos
     */
    public function actualizar(FinanzasDeuda $deuda, array $atributos): FinanzasDeuda
    {
        $pagado = max(0, (float) $deuda->monto_original - (float) $deuda->saldo_pendiente);
        $atributos['saldo_pendiente'] = max(0, (float) $atributos['monto_original'] - $pagado);

        return $this->deudas->update($deuda, $atributos);
    }

    /**
     * Registra un abono (parcial o total). Un abono pendiente no baja el saldo
     * de la deuda hasta que se confirma.
     *
     * @return array{message: string, saldo_pendiente: float, pendiente?: bool, pagada?: bool}
     */
    public function registrarPago(FinanzasDeuda $deuda, FinanzasSubcuenta $bolsillo, float $monto, bool $pendiente = false): array
    {
        $monto = round($monto, 2);
        $saldoDeuda = (float) $deuda->saldo_pendiente;

        if ($monto > $saldoDeuda) {
            throw new BusinessRuleException('El abono supera el saldo pendiente de la deuda.', extra: ['max' => $saldoDeuda]);
        }

        /** @var FinanzasCuenta $cuenta */
        $cuenta = $this->cuentas->findOrFail((int) $bolsillo->finanzas_cuenta_id);
        $saldoBolsillo = $this->movimientos->saldoSubcuenta((int) $bolsillo->id);

        if ($monto > $saldoBolsillo) {
            $disponible = max(0, $saldoBolsillo);

            throw new BusinessRuleException(
                'El bolsillo no tiene saldo suficiente. Máximo disponible: '.$cuenta->moneda.' '.number_format($disponible, 2).'.',
                extra: ['max' => $disponible],
            );
        }

        $this->deudas->transaction(function () use ($deuda, $cuenta, $bolsillo, $monto, $pendiente): void {
            $this->movimientoService->registrar(new MovimientoData(
                tipo: FinanzasMovimiento::TIPO_GASTO,
                cuentaId: (int) $cuenta->id,
                monto: $monto,
                descripcion: 'Pago deuda: '.$deuda->nombre,
                fecha: now()->toDateString(),
                subcuentaId: (int) $bolsillo->id,
                propietarioId: $cuenta->finanzas_propietario_id !== null ? (int) $cuenta->finanzas_propietario_id : null,
                categoria: 'Deudas',
                deudaId: (int) $deuda->id,
                pendiente: $pendiente,
            ));

            if (! $pendiente) {
                $deuda->decrement('saldo_pendiente', $monto);
            }
        });

        if ($pendiente) {
            return ['message' => 'Pago dejado como pendiente.', 'pendiente' => true, 'saldo_pendiente' => $saldoDeuda];
        }

        $saldoFinal = (float) $deuda->refresh()->saldo_pendiente;

        return [
            'message' => $saldoFinal <= 0 ? 'Deuda pagada por completo.' : 'Abono registrado.',
            'saldo_pendiente' => $saldoFinal,
            'pagada' => $saldoFinal <= 0,
        ];
    }
}

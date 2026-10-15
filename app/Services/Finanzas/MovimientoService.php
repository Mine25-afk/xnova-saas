<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\MovimientoData;
use App\DTOs\Finanzas\TransferenciaData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasCuentaPorCobrar;
use App\Models\FinanzasDeuda;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use Illuminate\Support\Arr;

/**
 * Registro, anulación y confirmación de movimientos financieros.
 */
final class MovimientoService
{
    public function __construct(
        private readonly MovimientoRepository $movimientos,
        private readonly CuentaRepository $cuentas,
        private readonly SubcuentaRepository $subcuentas,
    ) {}

    public function registrar(MovimientoData $data): FinanzasMovimiento
    {
        return $this->movimientos->create($this->atributos($data));
    }

    /**
     * La edición manual no toca el vínculo con deudas/cobros ni el estado pendiente.
     */
    public function actualizar(FinanzasMovimiento $movimiento, MovimientoData $data): FinanzasMovimiento
    {
        $atributos = Arr::except($this->atributos($data), ['finanzas_deuda_id', 'finanzas_cuenta_por_cobrar_id', 'pendiente']);

        return $this->movimientos->update($movimiento, $atributos);
    }

    /**
     * Ingreso o gasto directo sobre un bolsillo, con fecha de hoy.
     */
    public function registrarEnBolsillo(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo, string $tipo, string $descripcion, float $monto): FinanzasMovimiento
    {
        return $this->registrar(new MovimientoData(
            tipo: $tipo,
            cuentaId: (int) $cuenta->id,
            monto: $monto,
            descripcion: $descripcion,
            fecha: now()->toDateString(),
            subcuentaId: (int) $bolsillo->id,
            propietarioId: $this->entero($cuenta->finanzas_propietario_id),
        ));
    }

    public function transferir(FinanzasCuenta $cuenta, FinanzasSubcuenta $origen, TransferenciaData $data): FinanzasMovimiento
    {
        if ($this->movimientos->saldoSubcuenta((int) $origen->id) <= 0) {
            throw new BusinessRuleException('Este bolsillo no tiene saldo positivo, no puedes transferir dinero.');
        }

        $destino = $data->crearBolsilloEspejo
            ? $this->bolsilloEspejo($cuenta, $origen, (int) $data->cuentaDestinoId)
            : $this->subcuentas->findOrFail((int) $data->subcuentaDestinoId);

        if ((int) $destino->id === (int) $origen->id) {
            throw new BusinessRuleException('El destino debe ser distinto al origen.');
        }

        return $this->registrar(new MovimientoData(
            tipo: FinanzasMovimiento::TIPO_TRANSFERENCIA,
            cuentaId: (int) $cuenta->id,
            monto: $data->monto,
            descripcion: $data->descripcion ?? 'Transferencia a '.$destino->nombre,
            fecha: now()->toDateString(),
            subcuentaId: (int) $origen->id,
            cuentaDestinoId: (int) $destino->finanzas_cuenta_id,
            subcuentaDestinoId: (int) $destino->id,
            propietarioId: $this->entero($cuenta->finanzas_propietario_id),
            categoria: 'Transferencia',
            pendiente: $data->pendiente,
        ));
    }

    /**
     * Anula un movimiento conservando su monto original para auditoría. Si era
     * un abono/cobro ya aplicado, el saldo de la deuda o cuenta por cobrar vuelve a subir.
     */
    public function anular(FinanzasMovimiento $movimiento, string $motivo): void
    {
        if ($movimiento->isAnulado()) {
            throw new BusinessRuleException('Este movimiento ya está anulado.');
        }

        $this->movimientos->transaction(function () use ($movimiento, $motivo): void {
            $monto = (float) $movimiento->monto;

            // Un pendiente nunca descontó el saldo, así que no hay nada que revertir.
            if (! $movimiento->isPendiente()) {
                $this->revertirSaldo($movimiento->deuda, $monto);
                $this->revertirSaldo($movimiento->cuentaPorCobrar, $monto);
            }

            $movimiento->update([
                'monto_original' => $monto,
                'monto' => 0,
                'pendiente' => false,
                'anulado_en' => now(),
                'motivo_anulacion' => $motivo,
            ]);
        });
    }

    /**
     * Confirma una operación pendiente: ahora sí afecta saldos y aplica el
     * abono/cobro sobre la deuda o cuenta por cobrar asociada.
     */
    public function confirmarPendiente(FinanzasMovimiento $movimiento): void
    {
        $this->asegurarPendiente($movimiento);

        $this->movimientos->transaction(function () use ($movimiento): void {
            $monto = (float) $movimiento->monto;

            $this->aplicarSaldo($movimiento->deuda, $monto);
            $this->aplicarSaldo($movimiento->cuentaPorCobrar, $monto);

            $movimiento->update([
                'pendiente' => false,
                'confirmado_en' => now(),
            ]);
        });
    }

    /**
     * Cancela una operación pendiente. Como no afectó saldos, basta con anularla.
     */
    public function cancelarPendiente(FinanzasMovimiento $movimiento, ?string $motivo = null): void
    {
        $this->asegurarPendiente($movimiento);

        $motivo = trim((string) $motivo);

        $movimiento->update([
            'monto_original' => $movimiento->monto,
            'monto' => 0,
            'pendiente' => false,
            'anulado_en' => now(),
            'motivo_anulacion' => $motivo !== '' ? $motivo : 'Pendiente cancelado',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function atributos(MovimientoData $data): array
    {
        $cuenta = $this->cuentas->findOrFail($data->cuentaId);
        $esTransferencia = $data->tipo === FinanzasMovimiento::TIPO_TRANSFERENCIA;

        $cuentaDestinoId = null;
        $subcuentaDestinoId = null;

        if ($esTransferencia && $data->cuentaDestinoId !== null) {
            $cuentaDestino = $this->cuentas->findOrFail($data->cuentaDestinoId);
            $cuentaDestinoId = (int) $cuentaDestino->id;
            $subcuentaDestinoId = $this->subcuentas->resolverParaCuenta($cuentaDestino, $data->subcuentaDestinoId);
        }

        return [
            'finanzas_cuenta_id' => (int) $cuenta->id,
            'finanzas_subcuenta_id' => $this->subcuentas->resolverParaCuenta($cuenta, $data->subcuentaId),
            'finanzas_cuenta_destino_id' => $cuentaDestinoId,
            'finanzas_subcuenta_destino_id' => $subcuentaDestinoId,
            'finanzas_propietario_id' => $data->propietarioId,
            'finanzas_deuda_id' => $data->deudaId,
            'finanzas_cuenta_por_cobrar_id' => $data->cuentaPorCobrarId,
            'tipo' => $data->tipo,
            'descripcion' => $data->descripcion,
            'categoria' => $data->categoria,
            'monto' => round($data->monto, 2),
            'fecha' => $data->fecha,
            'pendiente' => $data->pendiente,
        ];
    }

    /**
     * El destino no tiene un bolsillo con este propósito: se crea (o reactiva)
     * uno con el mismo nombre para que la trazabilidad viaje con el dinero.
     */
    private function bolsilloEspejo(FinanzasCuenta $cuentaOrigen, FinanzasSubcuenta $origen, int $cuentaDestinoId): FinanzasSubcuenta
    {
        $cuentaDestino = $this->cuentas->findOrFail($cuentaDestinoId);

        if ((int) $cuentaDestino->id === (int) $cuentaOrigen->id) {
            throw new BusinessRuleException('Este bolsillo ya existe en la cuenta seleccionada.');
        }

        $destino = $this->subcuentas->deCuentaPorNombre((int) $cuentaDestino->id, (string) $origen->nombre);

        if ($destino !== null) {
            if (! $destino->active) {
                $destino->update(['active' => true]);
            }

            return $destino;
        }

        return $this->subcuentas->create([
            'finanzas_cuenta_id' => $cuentaDestino->id,
            'nombre' => $origen->nombre,
            'icono' => $origen->icono,
            'es_general' => false,
            'active' => true,
        ]);
    }

    private function asegurarPendiente(FinanzasMovimiento $movimiento): void
    {
        if (! $movimiento->isPendiente() || $movimiento->isAnulado()) {
            throw new BusinessRuleException('Esta operación ya no está pendiente.');
        }
    }

    private function aplicarSaldo(FinanzasDeuda|FinanzasCuentaPorCobrar|null $registro, float $monto): void
    {
        if ($registro === null) {
            return;
        }

        $registro->update(['saldo_pendiente' => max(0, (float) $registro->saldo_pendiente - $monto)]);
    }

    private function revertirSaldo(FinanzasDeuda|FinanzasCuentaPorCobrar|null $registro, float $monto): void
    {
        if ($registro === null) {
            return;
        }

        $registro->update([
            'saldo_pendiente' => min((float) $registro->monto_original, (float) $registro->saldo_pendiente + $monto),
        ]);
    }

    private function entero(mixed $valor): ?int
    {
        return $valor === null ? null : (int) $valor;
    }
}

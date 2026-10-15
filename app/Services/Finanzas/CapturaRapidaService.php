<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\MovimientoData;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\MovimientoRepository;

/**
 * Captura rápida (botón flotante global): registrar un gasto o ingreso en pocos toques.
 */
final class CapturaRapidaService
{
    public function __construct(
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoRepository $movimientos,
        private readonly GastoFijoRepository $gastosFijos,
        private readonly MovimientoService $movimientoService,
        private readonly SeleccionCuentasService $seleccion,
    ) {}

    /**
     * Opciones del modal: cuentas con bolsillos y saldo, propietarios,
     * categorías usadas, favoritos y la última cuenta/bolsillo usados.
     *
     * @return array<string, mixed>
     */
    public function opciones(): array
    {
        $cuentas = collect($this->seleccion->cuentasConBolsillos())->values();
        $saldos = $this->movimientos->saldosPorCuenta($cuentas->pluck('id')->map(fn ($id): int => (int) $id)->all());

        $cuentas = $cuentas->map(fn (array $cuenta): array => $cuenta + [
            'saldo' => $saldos[(int) $cuenta['id']] ?? 0.0,
        ])->map(fn (array $cuenta): array => array_merge($cuenta, [
            'propietario_nombre' => $cuenta['propietario_nombre'] ?? 'Sin propietario',
        ]));

        $propietarios = collect($this->seleccion->propietariosDe($cuentas->all()))
            ->sortBy('nombre')
            ->values();

        $categorias = $this->movimientos->categoriasUsadas()
            ->merge($this->gastosFijos->categoriasUsadas())
            ->unique()
            ->sort()
            ->values();

        $ultimo = $this->movimientos->ultimoGastoOIngreso();

        return [
            'cuentas' => $cuentas->values(),
            'propietarios' => $propietarios,
            'categorias' => $categorias,
            'favoritos' => $this->movimientos->favoritos(),
            'ultima_cuenta_id' => $ultimo !== null ? (int) $ultimo->finanzas_cuenta_id : ($cuentas->first()['id'] ?? null),
            'ultima_seleccion' => $ultimo !== null ? [
                'cuenta_id' => (int) $ultimo->finanzas_cuenta_id,
                'subcuenta_id' => $ultimo->finanzas_subcuenta_id !== null ? (int) $ultimo->finanzas_subcuenta_id : null,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function registrar(string $tipo, int $cuentaId, ?int $subcuentaId, float $monto, ?string $descripcion, ?string $categoria, ?string $fecha): array
    {
        $cuenta = $this->cuentas->findOrFail($cuentaId);
        $descripcion = trim((string) $descripcion);

        if ($descripcion === '') {
            $descripcion = $tipo === FinanzasMovimiento::TIPO_INGRESO ? 'Ingreso rápido' : 'Gasto rápido';
        }

        $movimiento = $this->movimientoService->registrar(new MovimientoData(
            tipo: $tipo,
            cuentaId: (int) $cuenta->id,
            monto: $monto,
            descripcion: $descripcion,
            fecha: $fecha ?? now()->toDateString(),
            subcuentaId: $subcuentaId,
            propietarioId: $cuenta->finanzas_propietario_id !== null ? (int) $cuenta->finanzas_propietario_id : null,
            categoria: $categoria,
        ));

        $bolsilloId = (int) $movimiento->finanzas_subcuenta_id;

        return [
            'message' => $tipo === FinanzasMovimiento::TIPO_INGRESO ? 'Ingreso registrado.' : 'Gasto registrado.',
            'movimiento_id' => $movimiento->id,
            'saldo_cuenta' => $this->movimientos->saldoCuenta((int) $cuenta->id),
            'subcuenta_id' => $bolsilloId,
            'saldo_bolsillo' => $this->movimientos->saldoSubcuenta($bolsilloId),
            'moneda' => $cuenta->moneda,
        ];
    }
}

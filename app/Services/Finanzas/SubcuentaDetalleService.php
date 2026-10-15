<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasCuenta;
use App\Models\FinanzasDeuda;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\DeudaRepository;
use App\Repositories\Finanzas\GastoFijoRepository;
use App\Repositories\Finanzas\IngresoFijoRepository;
use App\Repositories\Finanzas\MovimientoRepository;

/**
 * Arma los datos de la pantalla de detalle de un bolsillo (acciones rápidas).
 */
final class SubcuentaDetalleService
{
    public function __construct(
        private readonly MovimientoRepository $movimientos,
        private readonly IngresoFijoRepository $ingresosFijos,
        private readonly GastoFijoRepository $gastosFijos,
        private readonly DeudaRepository $deudas,
        private readonly SeleccionCuentasService $seleccion,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function paraVista(FinanzasCuenta $cuenta, FinanzasSubcuenta $subcuenta, bool $puedePagarDeuda): array
    {
        $cuenta->load('propietario:id,nombre');

        $cuentasDestino = $this->seleccion->cuentasConBolsillos((int) $subcuenta->id);

        // Fijos del propietario de la cuenta más los generales; sin propietario se muestran todos.
        $propietarioId = $cuenta->finanzas_propietario_id !== null ? (int) $cuenta->finanzas_propietario_id : null;

        $deudas = $this->deudas->activasConSaldo();

        return [
            'cuenta' => $cuenta,
            'subcuenta' => $subcuenta,
            'saldo' => $this->movimientos->saldoSubcuenta((int) $subcuenta->id),
            'cuentasDestino' => $cuentasDestino,
            'propietariosDestino' => $this->seleccion->propietariosDe($cuentasDestino),
            'ingresosFijos' => $this->ingresosFijos->activosPara($propietarioId),
            'gastosFijos' => $this->gastosFijos->activosPara($propietarioId),
            'movimientos' => $this->movimientos->recientesDeSubcuenta((int) $subcuenta->id),
            'deudasPendientes' => $deudas->map(fn (FinanzasDeuda $deuda): array => [
                'id' => $deuda->id,
                'nombre' => $deuda->nombre,
                'icono' => $deuda->icono,
                'saldo_pendiente' => (float) $deuda->saldo_pendiente,
                'vencimiento' => $deuda->fecha_vencimiento?->format('d/m/Y'),
                'vencida' => $deuda->fecha_vencimiento !== null && $deuda->fecha_vencimiento->isPast(),
            ])->values()->all(),
            'totalDeudas' => (float) $deudas->sum('saldo_pendiente'),
            'puedePagarDeuda' => $puedePagarDeuda,
        ];
    }
}

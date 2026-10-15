<?php

namespace App\Services\Finanzas;

use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;

/**
 * Estructura cuentas → bolsillos (con saldo) que usan los asistentes de
 * transferencia, pago de deudas, cobros y captura rápida.
 */
final class SeleccionCuentasService
{
    public function __construct(
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoRepository $movimientos,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function cuentasConBolsillos(?int $excluirSubcuentaId = null): array
    {
        $cuentas = $this->cuentas->activasConBolsillos($excluirSubcuentaId)
            ->filter(fn (FinanzasCuenta $cuenta): bool => $cuenta->subcuentas->isNotEmpty());

        $subcuentaIds = $cuentas
            ->flatMap(fn (FinanzasCuenta $cuenta) => $cuenta->subcuentas->pluck('id'))
            ->map(fn ($id): int => (int) $id)
            ->all();

        $saldos = $this->movimientos->saldosPorSubcuenta($subcuentaIds);

        return $cuentas
            ->map(fn (FinanzasCuenta $cuenta): array => [
                'id' => $cuenta->id,
                'nombre' => $cuenta->nombre,
                'tipo' => $cuenta->tipo,
                'moneda' => $cuenta->moneda,
                'icono' => $cuenta->icono,
                'propietario_id' => $cuenta->finanzas_propietario_id,
                'propietario_nombre' => $cuenta->propietario?->nombre,
                'bolsillos' => $cuenta->subcuentas
                    ->map(fn (FinanzasSubcuenta $bolsillo): array => [
                        'id' => $bolsillo->id,
                        'nombre' => $bolsillo->nombre,
                        'icono' => $bolsillo->icono,
                        'es_general' => (bool) $bolsillo->es_general,
                        'saldo' => $saldos[(int) $bolsillo->id] ?? 0.0,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Propietarios únicos presentes en la estructura de cuentas.
     *
     * @param  list<array<string, mixed>>  $cuentas
     * @return list<array{id: mixed, nombre: mixed}>
     */
    public function propietariosDe(array $cuentas): array
    {
        return collect($cuentas)
            ->map(fn (array $cuenta): array => [
                'id' => $cuenta['propietario_id'],
                'nombre' => $cuenta['propietario_nombre'] ?? 'Sin propietario',
            ])
            ->unique(fn (array $propietario): string => (string) ($propietario['id'] ?? 'null'))
            ->values()
            ->all();
    }
}

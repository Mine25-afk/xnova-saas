<?php

namespace App\Services\Finanzas;

use App\DTOs\Finanzas\DistribucionData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\SubcuentaRepository;

/**
 * Bolsillos (subcuentas) de una cuenta y reparto de dinero entre ellos.
 */
final class BolsilloService
{
    public function __construct(
        private readonly SubcuentaRepository $subcuentas,
        private readonly MovimientoRepository $movimientos,
    ) {}

    /**
     * @param  array<string, mixed>  $atributos
     */
    public function crear(FinanzasCuenta $cuenta, array $atributos): FinanzasSubcuenta
    {
        return $this->subcuentas->create($atributos + [
            'finanzas_cuenta_id' => $cuenta->id,
            'active' => true,
        ]);
    }

    public function desactivar(FinanzasSubcuenta $bolsillo): void
    {
        if ($bolsillo->es_general) {
            throw BusinessRuleException::enCampo('bolsillo', 'El bolsillo general no se puede desactivar.');
        }

        $bolsillo->update(['active' => false]);
    }

    public function distribuir(FinanzasCuenta $cuenta, DistribucionData $data): void
    {
        if (in_array($data->origenSubcuentaId, $data->destinoIds(), true)) {
            throw BusinessRuleException::enCampo('destinos', 'Un bolsillo destino no puede ser igual al bolsillo origen.');
        }

        $nombres = $this->subcuentas->deCuenta((int) $cuenta->id, $data->destinoIds())->pluck('nombre', 'id');

        $this->movimientos->transaction(function () use ($cuenta, $data, $nombres): void {
            foreach ($data->destinos as $destino) {
                $this->movimientos->create([
                    'finanzas_cuenta_id' => $cuenta->id,
                    'finanzas_cuenta_destino_id' => $cuenta->id,
                    'finanzas_subcuenta_id' => $data->origenSubcuentaId,
                    'finanzas_subcuenta_destino_id' => $destino['subcuenta_id'],
                    'finanzas_propietario_id' => $cuenta->finanzas_propietario_id,
                    'tipo' => FinanzasMovimiento::TIPO_TRANSFERENCIA,
                    'descripcion' => 'Distribucion a '.($nombres[$destino['subcuenta_id']] ?? 'bolsillo'),
                    'categoria' => 'Distribucion',
                    'monto' => $destino['monto'],
                    'fecha' => $data->fecha,
                ]);
            }
        });
    }
}

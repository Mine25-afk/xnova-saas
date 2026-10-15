<?php

use App\Models\FinanzasGastoFijo;
use App\Services\Finanzas\FijoService;

it('inicializa el saldo restante solo en modo por pagos', function () {
    $servicio = new FijoService;

    expect($servicio->saldoInicial(true, 300.0))->toBe(300.0)
        ->and($servicio->saldoInicial(false, 300.0))->toBeNull();
});

it('recalcula el saldo restante al editar según el modo', function () {
    $servicio = new FijoService;
    $porPagos = new FinanzasGastoFijo(['preguntar_al_usar' => true, 'monto' => 100]);
    $normal = new FinanzasGastoFijo(['preguntar_al_usar' => false, 'monto' => 100]);

    expect($servicio->saldoAlActualizar($normal, true, 250.0))->toBe(['saldo_restante' => 250.0])
        ->and($servicio->saldoAlActualizar($porPagos, true, 250.0))->toBe([])
        ->and($servicio->saldoAlActualizar($porPagos, false, 250.0))->toBe(['saldo_restante' => null]);
});

it('no descuenta usos de fijos que no son por pagos', function () {
    expect((new FijoService)->descontarUso(new FinanzasGastoFijo(['preguntar_al_usar' => false]), 50.0))->toBe([]);
});

<?php

use App\Services\Finanzas\SaldoCalculator;

function movimiento(string $tipo, float $monto, ?int $origen, ?int $destino = null): array
{
    return ['tipo' => $tipo, 'monto' => $monto, 'origen' => $origen, 'destino' => $destino];
}

it('suma ingresos y ajustes y resta gastos', function () {
    $saldos = (new SaldoCalculator)->calcular([
        movimiento('ingreso', 1000, 1),
        movimiento('gasto', 250.5, 1),
        movimiento('ajuste', 50, 1),
        movimiento('ajuste', -20, 1),
    ], [1]);

    expect($saldos)->toBe([1 => 779.5]);
});

it('una transferencia resta al origen y suma al destino', function () {
    $saldos = (new SaldoCalculator)->calcular([
        movimiento('ingreso', 500, 1),
        movimiento('transferencia', 200, 1, 2),
    ], [1, 2]);

    expect($saldos)->toBe([1 => 300.0, 2 => 200.0]);
});

it('ignora ids no solicitados y devuelve cero para los que no tienen movimientos', function () {
    $saldos = (new SaldoCalculator)->calcular([
        movimiento('transferencia', 80, 9, 1),
    ], [1, 3]);

    expect($saldos)->toBe([1 => 80.0, 3 => 0.0]);
});

it('redondea a dos decimales para evitar ruido de punto flotante', function () {
    $saldos = (new SaldoCalculator)->calcular([
        movimiento('ingreso', 0.1, 1),
        movimiento('ingreso', 0.2, 1),
    ], [1]);

    expect($saldos[1])->toBe(0.3);
});

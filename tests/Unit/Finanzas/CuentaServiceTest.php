<?php

use App\DTOs\Finanzas\AjusteSaldoData;
use App\Exceptions\BusinessRuleException;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use App\Services\Finanzas\CuentaService;

beforeEach(function () {
    $this->movimientos = Mockery::mock(MovimientoRepository::class);
    $this->movimientos->shouldReceive('transaction')->andReturnUsing(fn (Closure $callback) => $callback());

    $this->servicio = new CuentaService(
        Mockery::mock(CuentaRepository::class),
        Mockery::mock(SubcuentaRepository::class),
        $this->movimientos,
    );

    $this->cuenta = (new FinanzasCuenta)->forceFill(['id' => 7, 'finanzas_propietario_id' => 3]);
});

it('no ajusta nada si el saldo real coincide con el actual', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->with(7)->andReturn(500.0);
    $this->movimientos->shouldNotReceive('create');

    expect($this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(500.004, [1 => 10.0])))->toBeFalse();
});

it('reparte una diferencia positiva como ajustes de suma', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->andReturn(500.0);
    $this->movimientos->shouldReceive('create')->twice()->withArgs(fn (array $datos) => $datos['tipo'] === 'ajuste' && $datos['monto'] > 0)->andReturn(new FinanzasMovimiento);

    expect($this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(600, [1 => 70.0, 2 => 30.0])))->toBeTrue();
});

it('rechaza asignaciones que exceden la diferencia', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->andReturn(500.0);

    $this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(550, [1 => 80.0]));
})->throws(BusinessRuleException::class, 'excede la diferencia');

it('exige asignar al menos un monto', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->andReturn(500.0);

    $this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(550, [1 => 0.0]));
})->throws(BusinessRuleException::class, 'Asigna al menos un monto');

it('no permite descontar de un bolsillo más de lo que tiene', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->andReturn(500.0);
    $this->movimientos->shouldReceive('saldosPorSubcuenta')->with([1])->andReturn([1 => 20.0]);

    $this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(450, [1 => 50.0]));
})->throws(BusinessRuleException::class, 'No puedes descontar');

it('crea ajustes negativos cuando el saldo real es menor', function () {
    $this->movimientos->shouldReceive('saldoCuenta')->andReturn(500.0);
    $this->movimientos->shouldReceive('saldosPorSubcuenta')->andReturn([1 => 200.0]);
    $this->movimientos->shouldReceive('create')->once()->withArgs(fn (array $datos) => $datos['monto'] === -50.0 && $datos['finanzas_subcuenta_id'] === 1)->andReturn(new FinanzasMovimiento);

    expect($this->servicio->ajustarSaldo($this->cuenta, new AjusteSaldoData(450, [1 => 50.0])))->toBeTrue();
});

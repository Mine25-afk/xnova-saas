<?php

use App\Models\FinanzasCuenta;
use App\Models\FinanzasDeuda;
use App\Models\FinanzasMovimiento;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Tenancy\TenantContext;
use Tests\Support\Escenario;

beforeEach(function () {
    $this->e = Escenario::crear();
    $xnovaId = $this->e->xnova->id;

    $this->cuenta = FinanzasCuenta::factory()->create(['xnova_id' => $xnovaId, 'nombre' => 'Banco']);
    $this->general = FinanzasSubcuenta::factory()->general()->deCuenta($this->cuenta)->create();
    $this->ahorro = FinanzasSubcuenta::factory()->deCuenta($this->cuenta)->create(['nombre' => 'Ahorro']);

    FinanzasMovimiento::factory()->enBolsillo($this->general)->create(['monto' => 1000]);

    $this->deuda = FinanzasDeuda::factory()->create(['xnova_id' => $xnovaId, 'monto_original' => 600, 'saldo_pendiente' => 600]);
});

function saldoBolsillo(object $test, FinanzasSubcuenta $bolsillo): float
{
    return app(TenantContext::class)->runAs($test->e->xnova->id, fn () => app(MovimientoRepository::class)->saldoSubcuenta($bolsillo->id));
}

it('crea una cuenta con su bolsillo General y saldo inicial', function () {
    $this->actingAs($this->e->user)
        ->post(route('finanzas.cuentas.store'), [
            'nombre' => 'Efectivo',
            'tipo' => 'efectivo',
            'moneda' => 'LPS',
            'saldo_inicial' => 250,
        ])
        ->assertRedirect(route('finanzas.cuentas.create'));

    $cuenta = FinanzasCuenta::query()->where('nombre', 'Efectivo')->firstOrFail();
    $general = FinanzasSubcuenta::query()->where('finanzas_cuenta_id', $cuenta->id)->where('es_general', true)->firstOrFail();

    expect($cuenta->xnova_id)->toBe($this->e->xnova->id)
        ->and(saldoBolsillo($this, $general))->toBe(250.0);
});

it('registra gastos e ingresos en un bolsillo y recalcula el saldo', function () {
    $this->actingAs($this->e->user);

    $this->postJson(route('finanzas.cuentas.subcuentas.gasto', [$this->cuenta->id, $this->general->id]), ['nombre' => 'Super', 'monto' => 150])
        ->assertOk();
    $this->postJson(route('finanzas.cuentas.subcuentas.ingreso', [$this->cuenta->id, $this->general->id]), ['nombre' => 'Venta', 'monto' => 50])
        ->assertOk();

    expect(saldoBolsillo($this, $this->general))->toBe(900.0);
});

it('transfiere entre bolsillos y una transferencia pendiente no mueve saldo hasta confirmarse', function () {
    $this->actingAs($this->e->user);

    $this->postJson(route('finanzas.cuentas.subcuentas.transferir', [$this->cuenta->id, $this->general->id]), [
        'finanzas_subcuenta_destino_id' => $this->ahorro->id,
        'monto' => 200,
    ])->assertOk();

    expect(saldoBolsillo($this, $this->ahorro))->toBe(200.0);

    $this->postJson(route('finanzas.cuentas.subcuentas.transferir', [$this->cuenta->id, $this->general->id]), [
        'finanzas_subcuenta_destino_id' => $this->ahorro->id,
        'monto' => 100,
        'pendiente' => true,
    ])->assertOk()->assertJsonPath('pendiente', true);

    expect(saldoBolsillo($this, $this->ahorro))->toBe(200.0);

    $pendiente = FinanzasMovimiento::query()->where('pendiente', true)->firstOrFail();
    $this->postJson(route('finanzas.pendientes.confirmar', $pendiente->id))->assertOk();

    expect(saldoBolsillo($this, $this->ahorro))->toBe(300.0)
        ->and(saldoBolsillo($this, $this->general))->toBe(700.0);
});

it('abona a una deuda desde un bolsillo y descuenta el saldo pendiente', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('finanzas.deudas.pagar', $this->deuda->id), [
            'finanzas_subcuenta_id' => $this->general->id,
            'monto' => 250,
        ])
        ->assertOk()
        ->assertJsonPath('saldo_pendiente', fn ($valor) => (float) $valor === 350.0);

    expect((float) $this->deuda->fresh()->saldo_pendiente)->toBe(350.0)
        ->and(saldoBolsillo($this, $this->general))->toBe(750.0);
});

it('no permite abonar más de lo que tiene el bolsillo', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('finanzas.deudas.pagar', $this->deuda->id), [
            'finanzas_subcuenta_id' => $this->ahorro->id,
            'monto' => 100,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('max', fn ($valor) => (float) $valor === 0.0);

    expect((float) $this->deuda->fresh()->saldo_pendiente)->toBe(600.0);
});

it('anular un abono pendiente no repone el saldo de la deuda', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('finanzas.cuentas.subcuentas.pagar-deuda', [$this->cuenta->id, $this->general->id]), [
            'finanzas_deuda_id' => $this->deuda->id,
            'monto' => 100,
            'pendiente' => true,
        ])
        ->assertOk();

    $pago = FinanzasMovimiento::query()->where('finanzas_deuda_id', $this->deuda->id)->firstOrFail();

    $this->postJson(route('finanzas.cuentas.subcuentas.movimientos.anular', [$this->cuenta->id, $this->general->id, $pago->id]), [
        'motivo' => 'Captura duplicada',
    ])->assertOk();

    expect((float) $this->deuda->fresh()->saldo_pendiente)->toBe(600.0)
        ->and($pago->fresh()->anulado_en)->not->toBeNull();
});

it('anular un abono aplicado sí repone el saldo de la deuda', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('finanzas.cuentas.subcuentas.pagar-deuda', [$this->cuenta->id, $this->general->id]), [
            'finanzas_deuda_id' => $this->deuda->id,
            'monto' => 100,
        ])
        ->assertOk();

    $pago = FinanzasMovimiento::query()->where('finanzas_deuda_id', $this->deuda->id)->firstOrFail();

    $this->postJson(route('finanzas.cuentas.subcuentas.movimientos.anular', [$this->cuenta->id, $this->general->id, $pago->id]), [
        'motivo' => 'Error de monto',
    ])->assertOk();

    expect((float) $this->deuda->fresh()->saldo_pendiente)->toBe(600.0)
        ->and(saldoBolsillo($this, $this->general))->toBe(1000.0);
});

it('ajusta el saldo real de la cuenta repartiendo la diferencia', function () {
    $this->actingAs($this->e->user)
        ->post(route('finanzas.cuentas.ajustar', $this->cuenta->id), [
            'saldo_real' => 1100,
            'ajustes' => [
                ['finanzas_subcuenta_id' => $this->general->id, 'monto' => 60],
                ['finanzas_subcuenta_id' => $this->ahorro->id, 'monto' => 40],
            ],
        ])
        ->assertRedirect(route('finanzas.cuentas.show', $this->cuenta->id));

    expect(saldoBolsillo($this, $this->general))->toBe(1060.0)
        ->and(saldoBolsillo($this, $this->ahorro))->toBe(40.0);
});

it('exporta el libro de movimientos en CSV solo con datos de la empresa', function () {
    $otra = Escenario::crear();
    $cuentaAjena = FinanzasCuenta::factory()->create(['xnova_id' => $otra->xnova->id]);
    $bolsilloAjeno = FinanzasSubcuenta::factory()->general()->deCuenta($cuentaAjena)->create();
    FinanzasMovimiento::factory()->enBolsillo($bolsilloAjeno)->create(['descripcion' => 'Movimiento ajeno']);

    $csv = $this->actingAs($this->e->user)
        ->get(route('finanzas.reportes.excel', 'libro-movimientos'))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Libro de movimientos')
        ->not->toContain('Movimiento ajeno');
});

it('registra un gasto con la captura rápida en el bolsillo General', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('finanzas.quick.store'), [
            'tipo' => 'gasto',
            'finanzas_cuenta_id' => $this->cuenta->id,
            'monto' => 75.5,
        ])
        ->assertOk()
        ->assertJsonPath('subcuenta_id', $this->general->id)
        ->assertJsonPath('saldo_bolsillo', fn ($valor) => (float) $valor === 924.5);
});

it('exige permiso para entrar a finanzas', function () {
    $sinPermisos = Escenario::crear(['citas.ver']);

    $this->actingAs($sinPermisos->user)
        ->get(route('finanzas.dashboard'))
        ->assertForbidden();
});

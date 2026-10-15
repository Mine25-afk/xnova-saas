<?php

use App\Models\Cita;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Xnova;
use App\Tenancy\Exceptions\TenantMismatchException;
use App\Tenancy\Exceptions\TenantNotResolvedException;
use App\Tenancy\TenantContext;
use Tests\Support\Escenario;

beforeEach(function () {
    $this->a = Escenario::crear();
    $this->b = Escenario::crear();

    $this->cuentaB = FinanzasCuenta::factory()->create(['xnova_id' => $this->b->xnova->id]);
    $this->bolsilloB = FinanzasSubcuenta::factory()->general()->deCuenta($this->cuentaB)->create();
    $this->citaB = Cita::factory()->create([
        'xnova_id' => $this->b->xnova->id,
        'cliente_dni' => $this->b->cliente->dni,
        'colaborador_dni' => $this->b->colaborador->dni,
        'sucursal_Id' => $this->b->sucursal->id,
    ]);
});

it('no permite ver una cuenta de otra empresa', function () {
    $this->actingAs($this->a->user)
        ->get(route('finanzas.cuentas.show', $this->cuentaB->id))
        ->assertNotFound();
});

it('no permite editar ni cancelar una cita de otra empresa', function () {
    $this->actingAs($this->a->user);

    $this->putJson(route('citas.update', $this->citaB->id), ['descripcion' => 'x'])->assertNotFound();
    $this->patchJson(route('citas.cancel', $this->citaB->id))->assertNotFound();

    expect(Cita::query()->acrossTenants()->find($this->citaB->id)->estado_id)->toBe(Cita::ESTADO_AUTORIZADA_ID);
});

it('no permite registrar movimientos en un bolsillo de otra empresa', function () {
    $this->actingAs($this->a->user)
        ->postJson(route('finanzas.cuentas.subcuentas.gasto', [$this->cuentaB->id, $this->bolsilloB->id]), [
            'nombre' => 'Intruso',
            'monto' => 10,
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('finanzas_movimientos', ['descripcion' => 'Intruso']);
});

it('rechaza ids de otra empresa en la validación (exists por tenant)', function () {
    $this->actingAs($this->a->user)
        ->postJson(route('finanzas.quick.store'), [
            'tipo' => 'gasto',
            'finanzas_cuenta_id' => $this->cuentaB->id,
            'monto' => 10,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('finanzas_cuenta_id');
});

it('los listados solo devuelven datos de la propia empresa', function () {
    $respuesta = $this->actingAs($this->a->user)->getJson(route('listados.obtener', 'sucursales'))->assertOk();

    $ids = collect($respuesta->json('opciones'))->pluck('valor')->all();

    expect($ids)->toContain($this->a->sucursal->id)
        ->not->toContain($this->b->sucursal->id);
});

it('no permite ver usuarios de otra empresa aunque User no tenga scope', function () {
    $this->actingAs($this->a->user)
        ->get(route('usuarios.show', $this->b->user->id))
        ->assertNotFound();
});

it('responde 403 a un usuario sin empresa en lugar de usar otra empresa', function () {
    $sinEmpresa = User::factory()->create();

    $this->actingAs($sinEmpresa)->get(route('dashboard'))->assertForbidden();
});

it('responde 403 si la empresa del usuario está inactiva', function () {
    $this->a->xnova->update(['active' => false]);

    $this->actingAs($this->a->user)->getJson(route('finanzas.quick.opciones'))->assertForbidden();
});

it('el scope global no devuelve filas sin tenant resuelto (fail-closed)', function () {
    expect(Sucursal::query()->count())->toBe(0)
        ->and(Sucursal::query()->acrossTenants()->count())->toBe(2);
});

it('asigna xnova_id automáticamente al crear dentro de un tenant', function () {
    $sucursal = app(TenantContext::class)->runAs($this->a->xnova->id, fn () => Sucursal::query()->create(['nombre' => 'Nueva', 'horario_id' => 0]));

    expect($sucursal->xnova_id)->toBe($this->a->xnova->id);
});

it('impide crear registros sin empresa activa', function () {
    Sucursal::query()->create(['nombre' => 'Huérfana', 'horario_id' => 0]);
})->throws(TenantNotResolvedException::class);

it('impide crear o mover registros hacia otra empresa', function () {
    $contexto = app(TenantContext::class);

    expect(fn () => $contexto->runAs($this->a->xnova->id, fn () => Sucursal::query()->create([
        'nombre' => 'Ajena',
        'horario_id' => 0,
        'xnova_id' => $this->b->xnova->id,
    ])))->toThrow(TenantMismatchException::class);

    expect(fn () => $contexto->runAs($this->a->xnova->id, fn () => $this->a->sucursal->update(['xnova_id' => $this->b->xnova->id])))
        ->toThrow(TenantMismatchException::class);
});

it('cada empresa solo ve sus propios registros dentro de su contexto', function () {
    $contexto = app(TenantContext::class);

    $contexto->runAs($this->a->xnova->id, function () {
        expect(Cita::query()->count())->toBe(0)
            ->and(FinanzasCuenta::query()->find($this->cuentaB->id))->toBeNull();
    });

    $contexto->runAs($this->b->xnova->id, function () {
        expect(Cita::query()->count())->toBe(1);
    });

    expect(Xnova::query()->count())->toBe(2);
});

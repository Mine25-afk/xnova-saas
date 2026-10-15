<?php

use App\Tenancy\Exceptions\TenantNotResolvedException;
use App\Tenancy\TenantContext;

it('no tiene tenant si nadie lo fijó ni hay usuario autenticado', function () {
    $contexto = app(TenantContext::class);

    expect($contexto->id())->toBeNull()
        ->and($contexto->hasTenant())->toBeFalse();
});

it('lanza 403 al pedir un tenant obligatorio inexistente', function () {
    app(TenantContext::class)->idOrFail();
})->throws(TenantNotResolvedException::class);

it('runAs cambia de empresa temporalmente y restaura la anterior', function () {
    $contexto = app(TenantContext::class);
    $contexto->set(1);

    $dentro = $contexto->runAs(2, fn () => $contexto->id());

    expect($dentro)->toBe(2)
        ->and($contexto->id())->toBe(1);
});

it('restaura la empresa anterior aunque el callback falle', function () {
    $contexto = app(TenantContext::class);
    $contexto->set(1);

    try {
        $contexto->runAs(2, fn () => throw new RuntimeException('falla'));
    } catch (RuntimeException) {
    }

    expect($contexto->id())->toBe(1);
});

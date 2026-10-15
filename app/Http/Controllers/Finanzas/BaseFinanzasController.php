<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Concerns\RespondeListados;
use App\Http\Controllers\Controller;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Models\User;
use App\Models\Xnova;
use App\Services\PermissionService;
use App\Tenancy\TenantContext;

/**
 * Base de los controladores del módulo Finanzas. Los controladores solo
 * traducen HTTP ↔ dominio: validan con Form Requests, delegan en servicios y
 * eligen la respuesta.
 */
abstract class BaseFinanzasController extends Controller
{
    use RespondeListados;

    protected function monedaEmpresa(): string
    {
        $empresa = Xnova::query()->find(app(TenantContext::class)->idOrFail());

        return $empresa?->monedaPorDefecto() ?? Xnova::MONEDA_POR_DEFECTO;
    }

    protected function puede(string $permiso): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(PermissionService::class)->userHasPermission($user, $permiso);
    }

    protected function asegurarBolsilloDeCuenta(FinanzasCuenta $cuenta, FinanzasSubcuenta $bolsillo): void
    {
        $this->authorize('view', $cuenta);
        $this->authorize('view', $bolsillo);

        abort_if((int) $bolsillo->finanzas_cuenta_id !== (int) $cuenta->id, 404);
    }
}

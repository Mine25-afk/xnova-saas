<?php

namespace App\Http\Controllers\Concerns;

use App\Tenancy\TenantContext;

/**
 * Acceso a la empresa activa para controladores que aún la necesitan de
 * forma explícita (reglas de validación, límites de suscripción, rutas de archivos).
 */
trait UsaEmpresaActual
{
    protected function empresaId(): int
    {
        return app(TenantContext::class)->idOrFail();
    }
}

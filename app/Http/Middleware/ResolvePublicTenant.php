<?php

namespace App\Http\Middleware;

use App\Models\Xnova;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas públicas (reserva de citas): la empresa sale del identificador público
 * de la URL, no de una sesión. Una empresa inexistente o inactiva da 404.
 */
final class ResolvePublicTenant
{
    public const ATRIBUTO = 'empresa_publica';

    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $empresa = Xnova::query()
            ->where('active', true)
            ->where('reservacion_uid', (string) $request->route('uid'))
            ->firstOrFail();

        $this->tenant->set((int) $empresa->id);
        $request->attributes->set(self::ATRIBUTO, $empresa);

        return $next($request);
    }
}

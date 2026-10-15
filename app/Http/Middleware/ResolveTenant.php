<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Xnova;
use App\Tenancy\Exceptions\TenantNotResolvedException;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la empresa del usuario autenticado en el TenantContext.
 *
 * Un usuario sin empresa, o con una empresa inactiva, recibe 403: nunca se
 * cae a "la primera empresa" de la base de datos.
 */
final class ResolveTenant
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new TenantNotResolvedException('Debes iniciar sesión para continuar.');
        }

        $xnovaId = (int) ($user->xnova_id ?? 0);

        $empresaActiva = $xnovaId > 0 && Xnova::query()
            ->whereKey($xnovaId)
            ->where('active', true)
            ->exists();

        if (! $empresaActiva) {
            throw new TenantNotResolvedException('Tu usuario no pertenece a una empresa activa.');
        }

        $this->tenant->set($xnovaId);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function __construct(
        protected PermissionService $permissionService
    ) {
    }

    public function handle(Request $request, Closure $next, string $permissionCode): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $this->permissionService->userHasPermission($request->user(), $permissionCode)) {
            abort(403, 'No tienes permiso para entrar a esta pantalla.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\SuperAdmins;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! SuperAdmins::has($request->user())) {
            abort(403, 'Acceso restringido a superadministradores.');
        }

        return $next($request);
    }
}

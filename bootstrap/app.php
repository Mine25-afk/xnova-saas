<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsureUserHasPermission::class,
            'superadmin' => EnsureSuperAdmin::class,
            'tenant' => ResolveTenant::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'paypal/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Las reglas de negocio se reportan al usuario, no al log de errores.
        $exceptions->dontReport(BusinessRuleException::class);
    })->create();

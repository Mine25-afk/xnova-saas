<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Xnova;
use App\Services\NavigationService;
use App\Services\PermissionService;
use App\Support\SuperAdmins;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una instancia por request/job: el tenant nunca se filtra entre peticiones.
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $user = auth()->user();

            if (! $user instanceof User) {
                return;
            }

            $permissionService = app(PermissionService::class);
            $tenantId = app(TenantContext::class)->id();
            $empresa = $tenantId !== null ? Xnova::query()->find($tenantId) : null;

            $view->with('layoutNavigation', app(NavigationService::class)->forUser($user));
            $view->with('currentUserRoleName', $user->rol?->nombre ?? 'Sin rol');
            $view->with('currentUserPermissionCodes', $permissionService->permissionCodesForUser($user));
            $view->with('isCompanyCreator', $permissionService->isCompanyCreator($user));
            $view->with('isSuperAdmin', SuperAdmins::has($user));
            $view->with('monedaDefault', $empresa?->monedaPorDefecto() ?? Xnova::MONEDA_POR_DEFECTO);
        });
    }
}

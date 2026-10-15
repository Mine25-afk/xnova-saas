<?php

namespace App\Providers;

use App\Services\NavigationService;
use App\Services\PermissionService;
use App\Support\SuperAdmins;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();
            $permissionService = app(PermissionService::class);
            $navigation = app(NavigationService::class)->forUser($user);
            $permissionCodes = $permissionService->permissionCodesForUser($user);
            $isCompanyCreator = $permissionService->isCompanyCreator($user);
            $monedaDefault = $user?->xnova_id
                ? (\App\Models\Xnova::query()->where('id', $user->xnova_id)->value('moneda_default') ?? 'LPS')
                : 'LPS';

            $view->with('layoutNavigation', $navigation);
            $view->with('currentUserRoleName', $user?->rol?->nombre ?? 'Sin rol');
            $view->with('currentUserPermissionCodes', $permissionCodes);
            $view->with('isCompanyCreator', $isCompanyCreator);
            $view->with('isSuperAdmin', SuperAdmins::has($user));
            $view->with('monedaDefault', $monedaDefault);
        });
    }
}

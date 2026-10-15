<?php

namespace App\Services;

use App\Models\Permiso;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavigationService
{
    public function __construct(
        protected PermissionService $permissionService
    ) {
    }

    public function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $permissionCodes = $this->permissionService->permissionCodesForUser($user);

        if ($permissionCodes === []) {
            return [];
        }

        $moduleIcons = [
            'SEGURIDAD' => '<path d="M12 2l8 4v6c0 5.25-3.44 10.74-8 12-4.56-1.26-8-6.75-8-12V6l8-4z"/>',
            'CONFIGURACION' => '<path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54A.49.49 0 0 0 13.93 1h-3.86c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.32 7.47c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.07.65-.07.94s.03.63.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54c.03.24.23.41.47.41h3.86c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.12-.56 1.62-.94l2.39.96c.22.07.47 0 .59-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.01-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/>',
            'SOCIOS' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.67 0-8 1.34-8 4v2h10v-2c0-1.05.27-2.01.74-2.85C9.73 13.41 8.69 13 8 13zm8 0c-.69 0-1.73.41-2.74 1.15.47.84.74 1.8.74 2.85v2h10v-2c0-2.66-5.33-4-8-4z"/>',
            'CITAS' => '<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>',
            'INVENTARIO' => '<path d="M21 7.5 12 3 3 7.5 12 12l9-4.5z"/><path d="M3 12l9 4.5 9-4.5"/><path d="M3 16.5 12 21l9-4.5"/>',
            'FINANZAS' => '<path d="M12 2C6.48 2 2 5.13 2 9v6c0 3.87 4.48 7 10 7s10-3.13 10-7V9c0-3.87-4.48-7-10-7zm0 2c4.42 0 8 2.24 8 5s-3.58 5-8 5-8-2.24-8-5 3.58-5 8-5z"/><path d="M7 10h10v2H7z"/><path d="M11 7h2v8h-2z"/>',
        ];

        $screenIcons = [
            'usuarios.listado' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45v2h7v-2c0-2.66-5.33-4-8-4z"/>',
            'roles.listado' => '<path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>',
            'sucursales.listado' => '<path d="M12 7V3H2v18h20V7H12z"/>',
            'puestos.page' => '<path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-2 .89-2 2v11c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2z"/>',
            'socios.clientes.listado' => '<path d="M12 12c2.7 0 5.8 1.29 6 3v2H6v-2c.2-1.71 3.3-3 6-3zm0-2a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>',
            'socios.colaboradores.listado' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3z"/>',
            'citas.listado' => '<path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>',
            'citas.calendario' => '<path d="M7 10h5v5H7zM19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>',
            'citas.reportes' => '<path d="M5 9.2h3V19H5V9.2zm5.5-4.2h3V19h-3V5zm5.5 7h3V19h-3v-7z"/>',
            'inventario.productos.index' => '<path d="M21 7.5 12 3 3 7.5 12 12l9-4.5z"/><path d="M3 12l9 4.5 9-4.5"/><path d="M3 16.5 12 21l9-4.5"/>',
            'inventario.reservacion' => '<path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/><path d="M7 9h10"/><path d="M7 13h4"/>',
            'inventario.reportes' => '<path d="M5 19V9h3v10H5z"/><path d="M10.5 19V5h3v14h-3z"/><path d="M16 19v-7h3v7h-3z"/>',
            'finanzas.dashboard' => '<path d="M4 19h16v2H4z"/><path d="M6 17V9h3v8H6z"/><path d="M11 17V5h3v12h-3z"/><path d="M16 17v-6h3v6h-3z"/>',
            'finanzas.propietarios.index' => '<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/><path d="M4 20c.5-3.3 3.4-6 8-6s7.5 2.7 8 6H4z"/>',
            'finanzas.gastos-fijos.index' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/><path d="M7 8h10v2H7zM7 12h7v2H7zM7 16h5v2H7z"/>',
            'finanzas.cuentas.index' => '<path d="M3 6h18v12H3z"/><path d="M5 8h14v2H5z"/><path d="M7 14h4v2H7z"/>',
            'finanzas.deudas.index' => '<path d="M3 6h18v12H3z"/><path d="M3 9h18v2H3z"/><path d="M7 15h6v2H7z"/><path d="M16 15h2v2h-2z"/>',
            'finanzas.cuentas-por-cobrar.index' => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
            'finanzas.ingresos-fijos.index' => '<path d="M12 3l5 5h-3v8h-4V8H7l5-5z"/><path d="M5 19h14v2H5z"/>',
            'finanzas.movimientos.index' => '<path d="M7 7h11l-3-3 1.4-1.4L22 8l-5.6 5.4L15 12l3-3H7V7z"/><path d="M17 17H6l3 3-1.4 1.4L2 16l5.6-5.4L9 12l-3 3h11v2z"/>',
            'finanzas.reportes' => '<path d="M5 19V9h3v10H5z"/><path d="M10.5 19V5h3v14h-3z"/><path d="M16 19v-7h3v7h-3z"/>',
        ];

        $hiddenScreens = [
            // Pantallas ocultas del menú lateral aunque el usuario tenga permiso.
            // Se siguen usando para gestionar permisos sobre acciones internas.
            'finanzas.movimientos.index',
        ];

        $screenPermissions = Permiso::query()
            ->with([
                'modulo:id,nombre,codigo',
                'pantalla:id,nombre,codigo,ruta',
            ])
            ->whereNull('boton_id')
            ->whereIn('codigo', $permissionCodes)
            ->get()
            ->filter(fn ($permiso) => $permiso->pantalla && $permiso->modulo)
            ->filter(fn ($permiso) => filled($permiso->pantalla->ruta) && Route::has($permiso->pantalla->ruta))
            ->reject(fn ($permiso) => in_array($permiso->pantalla->ruta, $hiddenScreens, true))
            ->sortBy([
                fn ($a, $b) => $a->modulo_id <=> $b->modulo_id,
                fn ($a, $b) => $a->pantalla_id <=> $b->pantalla_id,
            ]);

        return $screenPermissions
            ->groupBy(fn ($permiso) => $permiso->modulo->codigo)
            ->map(function ($modulePermissions, $moduleCode) use ($moduleIcons, $screenIcons): array {
                $first = $modulePermissions->first();
                $items = $modulePermissions->map(function ($permiso) use ($screenIcons): array {
                    $routePatterns = $this->relatedRoutePatterns($permiso->pantalla->ruta);

                    return [
                        'title' => $permiso->pantalla->nombre,
                        'route_name' => $permiso->pantalla->ruta,
                        'href' => route($permiso->pantalla->ruta),
                        'active' => $this->isScreenActive($permiso->pantalla->ruta, $routePatterns),
                        'icon' => $screenIcons[$permiso->pantalla->ruta] ?? $screenIcons['roles.listado'],
                    ];
                });

                $items = $this->appendAppointmentQuickItems($moduleCode, $items, $screenIcons);

                return [
                    'title' => $first->modulo->nombre,
                    'icon' => $moduleIcons[$moduleCode] ?? $moduleIcons['CONFIGURACION'],
                    'active' => $items->contains(fn ($item) => $item['active']),
                    'items' => $items->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function isScreenActive(string $routeName, array $routePatterns): bool
    {
        $matchesRoute = collect($routePatterns)->contains(
            fn ($pattern) => request()->routeIs($pattern)
        );

        if (! $matchesRoute) {
            return false;
        }

        if ($routeName === 'citas.listado') {
            return ! in_array((string) request()->query('vista'), ['por-autorizar', 'hoy'], true);
        }

        return true;
    }

    protected function appendAppointmentQuickItems(string $moduleCode, $items, array $screenIcons)
    {
        if ($moduleCode !== 'CITAS') {
            return $items;
        }

        $quickItems = collect([
            [
                'title' => 'Por autorizar',
                'route_name' => 'citas.listado.quick.pending',
                'href' => route('citas.listado', ['vista' => 'por-autorizar']),
                'active' => request()->routeIs('citas.listado') && request()->query('vista') === 'por-autorizar',
                'icon' => $screenIcons['citas.listado'] ?? $screenIcons['roles.listado'],
            ],
            [
                'title' => 'Citas de hoy',
                'route_name' => 'citas.listado.quick.today',
                'href' => route('citas.listado', ['vista' => 'hoy']),
                'active' => request()->routeIs('citas.listado') && request()->query('vista') === 'hoy',
                'icon' => $screenIcons['citas.calendario'] ?? $screenIcons['citas.listado'] ?? $screenIcons['roles.listado'],
            ],
        ]);

        $extendedItems = collect();

        foreach ($items as $item) {
            $extendedItems->push($item);

            if (($item['route_name'] ?? null) === 'citas.listado') {
                foreach ($quickItems as $quickItem) {
                    $extendedItems->push($quickItem);
                }
            }
        }

        return $extendedItems;
    }

    protected function relatedRoutePatterns(string $routeName): array
    {
        if ($routeName === 'citas.listado') {
            return [
                'citas.listado',
                'citas.data',
                'citas.create',
                'citas.store',
                'citas.show',
                'citas.edit',
                'citas.update',
                'citas.finish',
                'citas.cancel',
            ];
        }

        if (in_array($routeName, ['citas.calendario', 'citas.reportes'], true)) {
            return [$routeName];
        }

        if ($routeName === 'inventario.productos.index') {
            return [
                'inventario.productos.index',
                'inventario.productos.data',
                'inventario.productos.create',
                'inventario.productos.store',
                'inventario.productos.edit',
                'inventario.productos.update',
            ];
        }

        if (in_array($routeName, ['inventario.reservacion', 'inventario.reportes'], true)) {
            return [$routeName];
        }

        if ($routeName === 'finanzas.dashboard' || $routeName === 'finanzas.reportes') {
            return [$routeName];
        }

        if (str_starts_with($routeName, 'finanzas.')) {
            $segments = explode('.', $routeName);

            return [$routeName, $segments[0].'.'.$segments[1].'.*'];
        }

        if (! str_contains($routeName, '.')) {
            return [$routeName];
        }

        $segments = explode('.', $routeName);
        array_pop($segments);

        return [
            $routeName,
            implode('.', $segments).'.*',
        ];
    }
}

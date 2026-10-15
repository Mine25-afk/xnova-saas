<?php

namespace App\Services;

use App\Models\Modulo;
use App\Models\Permiso;
use App\Models\User;
use App\Models\Xnova;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class PermissionService
{
    public function permissionCodesForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $user->loadMissing('rol.permisos');

        $rolePermissionCodes = $user->rol?->permisos
            ? $user->rol->permisos->pluck('codigo')->values()->all()
            : [];

        return array_values(array_unique([
            ...$rolePermissionCodes,
            ...$this->creatorOverridePermissionCodes($user),
        ]));
    }

    public function userHasPermission(?User $user, string $permissionCode): bool
    {
        if (! $user || $permissionCode === '') {
            return false;
        }

        return in_array($permissionCode, $this->permissionCodesForUser($user), true);
    }

    public function routePermissionCode(string $routeName): ?string
    {
        return Permiso::query()
            ->whereNull('boton_id')
            ->whereHas('pantalla', fn ($query) => $query->where('ruta', $routeName))
            ->value('codigo');
    }

    public function isCompanyCreator(?User $user): bool
    {
        if (! $user?->id) {
            return false;
        }

        if ($user->xnova_id) {
            return Xnova::query()
                ->where('id', $user->xnova_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return Xnova::query()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function permissionTree(?int $xnovaId = null): array
    {
        $allowedModuloIds = $this->allowedModuloIdsForXnova($xnovaId);

        return Modulo::query()
            ->with([
                'pantallas' => fn ($query) => $query->orderBy('id'),
                'pantallas.botones' => fn ($query) => $query->orderBy('id'),
                'pantallas.permisos' => fn ($query) => $query->orderBy('id'),
            ])
            ->when($allowedModuloIds !== null, fn ($q) => $q->whereIn('id', $allowedModuloIds))
            ->orderBy('id')
            ->get()
            ->map(function (Modulo $modulo): ?array {
                $screens = $modulo->pantallas
                    ->filter(fn ($pantalla) => filled($pantalla->ruta) && Route::has($pantalla->ruta))
                    ->map(function ($pantalla): array {
                        $viewPermission = $pantalla->permisos->firstWhere('boton_id', null);

                        return [
                            'id' => $pantalla->id,
                            'nombre' => $pantalla->nombre,
                            'codigo' => $pantalla->codigo,
                            'ruta' => $pantalla->ruta,
                            'permiso_ver' => $viewPermission ? [
                                'id' => $viewPermission->id,
                                'nombre' => $viewPermission->nombre,
                                'codigo' => $viewPermission->codigo,
                            ] : null,
                            'botones' => $pantalla->botones->map(function ($boton) use ($pantalla): array {
                                $permission = $pantalla->permisos->firstWhere('boton_id', $boton->id);

                                return [
                                    'id' => $boton->id,
                                    'nombre' => $boton->nombre,
                                    'codigo' => $boton->codigo,
                                    'permiso' => $permission ? [
                                        'id' => $permission->id,
                                        'nombre' => $permission->nombre,
                                        'codigo' => $permission->codigo,
                                    ] : null,
                                ];
                            })->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                if ($screens === []) {
                    return null;
                }

                return [
                    'id' => $modulo->id,
                    'nombre' => $modulo->nombre,
                    'codigo' => $modulo->codigo,
                    'pantallas' => $screens,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Returns module IDs allowed for the given xnova (resolved from current user when null),
     * based on xnova_tipo_modulo. Returns null if no scoping should be applied.
     */
    protected function allowedModuloIdsForXnova(?int $xnovaId = null): ?array
    {
        $xnovaId = $xnovaId ?? auth()->user()?->xnova_id;

        if (! $xnovaId) {
            return null;
        }

        $tipoId = Xnova::query()->where('id', $xnovaId)->value('xnova_tipo_id');

        if (! $tipoId) {
            return null;
        }

        $ids = DB::table('xnova_tipo_modulo')
            ->where('xnova_tipo_id', $tipoId)
            ->pluck('modulo_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids === [] ? null : $ids;
    }

    protected function creatorOverridePermissionCodes(User $user): array
    {
        if (! $this->isCompanyCreator($user)) {
            return [];
        }

        return Permiso::query()
            ->where(function ($query): void {
                $query
                    ->where('codigo', 'like', 'seguridad.roles.%')
                    ->orWhere('codigo', 'like', 'seguridad.usuarios.%');
            })
            ->pluck('codigo')
            ->values()
            ->all();
    }
}

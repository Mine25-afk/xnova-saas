<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\RoleLog;
use App\Models\Rol;
use App\Services\PermissionService;
use App\Services\SuscripcionLimiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class RolController extends Controller
{
    use UsaEmpresaActual;

    public function __construct(
        protected PermissionService $permissionService,
        protected SuscripcionLimiteService $suscripcionLimiteService
    ) {
    }

    public function listado(): View
    {
        return view('roles.index');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $roles = Rol::query()
            ->select(['id', 'nombre', 'created_at'])
            ->withCount(['permisos', 'users'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('roles.partials.rows', ['data' => $roles])->render(),
            'cards' => view('roles.partials.cards', ['data' => $roles])->render(),
            'pagination' => view('roles.partials.pagination', ['data' => $roles])->render(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'from' => $roles->firstItem(),
                'to' => $roles->lastItem(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('roles.form', [
            'data' => new Rol(['nombre' => '']),
            'isEditing' => false,
            'permissionTree' => $this->permissionService->permissionTree($this->empresaId()),
            'selectedPermissionIds' => [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validarRol($request);
        $this->suscripcionLimiteService->asegurarCupoPruebaRolesDisponible($this->empresaId());

        $rol = Rol::query()->create([
            'nombre' => $data['nombre'],
            'xnova_id' => $this->empresaId(),
        ]);

        $rol->permisos()->sync($data['permissions'] ?? []);
        $this->registrarLog($rol, 'Crear');

        return response()->json([
            'message' => 'Rol creado exitosamente.',
            'rol' => $rol->load('permisos'),
        ]);
    }

    public function show(Rol $rol): View
    {
        $this->asegurarRolActual($rol);

        $rol->load([
            'permisos:id,nombre,modulo_id,pantalla_id',
            'permisos.modulo:id,nombre,codigo',
            'logs' => fn ($query) => $query->orderByDesc('Fecha'),
            'logs.usuarioModifica:id,name,email',
        ])->loadCount(['permisos', 'users']);

        $modulosCount = $rol->permisos
            ->pluck('modulo_id')
            ->filter()
            ->unique()
            ->count();

        $pantallasCount = $rol->permisos
            ->pluck('pantalla_id')
            ->filter()
            ->unique()
            ->count();

        return view('roles.show', [
            'rol' => $rol,
            'modulosCount' => $modulosCount,
            'pantallasCount' => $pantallasCount,
        ]);
    }

    public function edit(Rol $rol): View
    {
        $this->asegurarRolActual($rol);

        return view('roles.form', [
            'data' => $rol->load('permisos'),
            'isEditing' => true,
            'permissionTree' => $this->permissionService->permissionTree($this->empresaId()),
            'selectedPermissionIds' => $rol->permisos->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function update(Request $request, Rol $rol): JsonResponse
    {
        $this->asegurarRolActual($rol);

        $data = $this->validarRol($request, $rol);

        $rol->update([
            'nombre' => $data['nombre'],
        ]);

        $rol->permisos()->sync($data['permissions'] ?? []);
        $this->registrarLog($rol, 'Editar');

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'rol' => $rol->fresh()->load('permisos'),
        ]);
    }

    protected function validarRol(Request $request, ?Rol $rol = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:roles,nombre,'.($rol?->id ?? 'NULL').',id,xnova_id,'.$this->empresaId(),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permisos,id'],
        ], [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
        ]);
    }

    protected function registrarLog(Rol $rol, string $accion): void
    {
        RoleLog::query()->updateOrCreate(
            [
                'Role_Id' => $rol->id,
                'Accion' => $accion,
            ],
            [
                'Fecha' => now(),
                'User_Modifica_Id' => auth()->id(),
            ],
        );
    }

    protected function asegurarRolActual(Rol $rol): void
    {
        $this->authorize('view', $rol);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Xnova;
use App\Services\SuscripcionLimiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class UsuarioController extends Controller
{
    use UsaEmpresaActual;

    public function __construct(
        protected SuscripcionLimiteService $suscripcionLimiteService
    ) {
    }

    public function listado(): View
    {
        return view('usuarios.index');
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
        $creatorUserId = $this->usuarioCreadorEmpresaId();

        $usuarios = User::query()
            ->select([
                'id',
                'name',
                'email',
                'rol_id',
                'sucursal_Id',
                'active',
                'created_at',
            ])
            ->with([
                'rol:id,nombre',
                'sucursal:id,nombre',
            ])
            ->where('xnova_id', $this->empresaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('rol', function (Builder $roleQuery) use ($search): void {
                            $roleQuery->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sucursal', function (Builder $branchQuery) use ($search): void {
                            $branchQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('usuarios.partials.rows', [
                'data' => $usuarios,
                'creatorUserId' => $creatorUserId,
            ])->render(),
            'cards' => view('usuarios.partials.cards', [
                'data' => $usuarios,
                'creatorUserId' => $creatorUserId,
            ])->render(),
            'pagination' => view('usuarios.partials.pagination', ['data' => $usuarios])->render(),
            'meta' => [
                'current_page' => $usuarios->currentPage(),
                'last_page' => $usuarios->lastPage(),
                'per_page' => $usuarios->perPage(),
                'total' => $usuarios->total(),
                'from' => $usuarios->firstItem(),
                'to' => $usuarios->lastItem(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', [
            'usuario' => new User([
                'active' => true,
            ]),
            'isEditing' => false,
            'isCompanyCreator' => false,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validarUsuario($request);
        $this->suscripcionLimiteService->asegurarCupoUsuariosDisponible($this->empresaId());

        $usuario = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'rol_id' => $payload['rol_id'],
            'sucursal_Id' => $payload['sucursal_Id'] ?? null,
            'colaborador_dni' => $payload['colaborador_dni'] ?? null,
            'xnova_id' => $this->empresaId(),
            'password' => null,
            'active' => true,
        ]);

        $this->registrarLog($usuario, 'Crear');

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'usuario' => $usuario->load([
                'rol:id,nombre',
                'sucursal:id,nombre',
                'colaborador:dni',
                'colaborador.socio:dni,nombres,apellidos',
            ]),
        ], 201);
    }

    public function show(User $usuario): View
    {
        $this->asegurarUsuarioActual($usuario);

        $usuario->load([
            'rol:id,nombre',
            'sucursal:id,nombre',
            'colaborador:dni',
            'colaborador.socio:dni,nombres,apellidos',
            'logs' => fn ($query) => $query->orderByDesc('Fecha'),
            'logs.usuarioModifica:id,name,email',
        ]);

        return view('usuarios.show', [
            'usuario' => $usuario,
        ]);
    }

    public function edit(User $usuario): View
    {
        $this->asegurarUsuarioActual($usuario);

        $usuario->load([
            'rol:id,nombre',
            'sucursal:id,nombre',
            'colaborador:dni',
            'colaborador.socio:dni,nombres,apellidos',
        ]);

        return view('usuarios.form', [
            'usuario' => $usuario,
            'isEditing' => true,
            'isCompanyCreator' => $this->esUsuarioCreadorEmpresa($usuario),
        ]);
    }

    public function update(Request $request, User $usuario): JsonResponse
    {
        $this->asegurarUsuarioActual($usuario);

        $payload = $this->validarUsuario($request, $usuario);

        if (
            $this->esUsuarioCreadorEmpresa($usuario)
            && strcasecmp((string) $payload['email'], (string) $usuario->email) !== 0
        ) {
            throw ValidationException::withMessages([
                'email' => 'No puedes modificar el correo del usuario creador de la empresa.',
            ]);
        }

        $usuario->update([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'rol_id' => $payload['rol_id'],
            'sucursal_Id' => $payload['sucursal_Id'] ?? null,
            'colaborador_dni' => $payload['colaborador_dni'] ?? null,
        ]);

        $this->registrarLog($usuario, 'Editar');

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario->fresh()->load([
                'rol:id,nombre',
                'sucursal:id,nombre',
                'colaborador:dni',
                'colaborador.socio:dni,nombres,apellidos',
            ]),
        ]);
    }

    public function destroy(User $usuario): JsonResponse
    {
        $this->asegurarUsuarioActual($usuario);

        if ($this->esUsuarioCreadorEmpresa($usuario)) {
            return response()->json([
                'message' => 'No puedes desactivar al usuario creador de la empresa.',
            ], 422);
        }

        $usuario->update([
            'active' => false,
        ]);

        $this->registrarLog($usuario, 'Desactivar');

        return response()->json([
            'message' => 'Usuario desactivado correctamente.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    public function activate(User $usuario): JsonResponse
    {
        $this->asegurarUsuarioActual($usuario);

        $usuario->update([
            'active' => true,
        ]);

        $this->registrarLog($usuario, 'Activar');

        return response()->json([
            'message' => 'Usuario activado correctamente.',
            'usuario' => $usuario->fresh(),
        ]);
    }

    protected function validarUsuario(Request $request, ?User $usuario = null): array
    {
        $xnovaId = $this->empresaId();

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'rol_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn (QueryBuilder $query) => $query->where('xnova_id', $xnovaId)),
            ],
            'sucursal_Id' => [
                'nullable',
                'integer',
                Rule::exists('sucursales', 'id')->where(fn (QueryBuilder $query) => $query->where('xnova_id', $xnovaId)),
            ],
            'colaborador_dni' => [
                'nullable',
                'string',
                Rule::exists('colaboradores', 'dni')->where(fn (QueryBuilder $query) => $query->where('xnova_id', $xnovaId)),
            ],
        ], [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'email.required' => 'El gmail es obligatorio.',
            'email.email' => 'Debes ingresar un gmail válido.',
            'email.unique' => 'Ya existe un usuario con ese gmail.',
            'rol_id.required' => 'Debes asignar un rol.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'sucursal_Id.exists' => 'La sucursal seleccionada no es válida.',
            'colaborador_dni.exists' => 'El empleado seleccionado no es válido.',
        ]);
    }

    protected function registrarLog(User $usuario, string $accion): void
    {
        UserLog::query()->updateOrCreate(
            [
                'User_Id' => $usuario->id,
                'Accion' => $accion,
            ],
            [
                'Fecha' => now(),
                'User_Modifica_Id' => auth()->id(),
            ],
        );
    }

    protected function asegurarUsuarioActual(User $usuario): void
    {
        $this->authorize('view', $usuario);
    }

    protected function usuarioCreadorEmpresaId(): ?int
    {
        $creatorUserId = Xnova::query()
            ->where('id', $this->empresaId())
            ->value('user_id');

        return $creatorUserId ? (int) $creatorUserId : null;
    }

    protected function esUsuarioCreadorEmpresa(User $usuario): bool
    {
        return (int) $usuario->id === (int) ($this->usuarioCreadorEmpresaId() ?? 0);
    }
}

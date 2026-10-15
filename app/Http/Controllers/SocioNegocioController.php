<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\Colaborador;
use App\Models\Puesto;
use App\Models\SocioLog;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Services\PermissionService;
use App\Services\SuscripcionLimiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class SocioNegocioController extends Controller
{
    use UsaEmpresaActual;

    private const TYPE_CLIENT = 'C';
    private const TYPE_COLLABORATOR = 'E';

    public function __construct(
        protected PermissionService $permissionService,
        protected SuscripcionLimiteService $suscripcionLimiteService
    ) {
    }

    public function listado_clientes(): View
    {
        return view('socios.clientes.index');
    }

    public function listado_colaboradores(): View
    {
        return view('socios.colaboradores.index');
    }

    public function data_clientes(Request $request): JsonResponse
    {
        return $this->dataByType($request, self::TYPE_CLIENT, 'socios.clientes.partials');
    }

    public function data_colaboradores(Request $request): JsonResponse
    {
        return $this->dataByType($request, self::TYPE_COLLABORATOR, 'socios.colaboradores.partials');
    }

    public function create(string $tipo): View
    {
        $tipo = $this->normalizeTipo($tipo);
        $this->authorizeTipoAction($tipo, 'crear');

        return $this->formView(
            new SocioNegocio([
                'fecha_nacimiento' => now()->toDateString(),
                'sexo' => 'M',
                'tipo' => $tipo,
                'active' => true,
                'es_por_defecto' => false,
                'xnova_id' => $this->empresaId(),
            ]),
            $tipo,
            false
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tipo = $this->normalizeTipo((string) $request->string('tipo'));
        $this->authorizeTipoAction($tipo, 'crear');
        $payload = $this->validatePayload($request, $tipo);

        if ($this->isCollaboratorType($tipo)) {
            $this->suscripcionLimiteService->asegurarCupoPruebaColaboradoresDisponible($this->empresaId());
        } else {
            $this->suscripcionLimiteService->asegurarCupoPruebaClientesDisponible($this->empresaId());
        }

        $socio = DB::transaction(function () use ($payload, $tipo): SocioNegocio {
            $socio = SocioNegocio::query()->create([
                'dni' => $payload['dni'],
                'nombres' => $payload['nombres'],
                'apellidos' => $payload['apellidos'],
                'fecha_nacimiento' => $payload['fecha_nacimiento'],
                'telefono' => $payload['telefono'],
                'correo' => $payload['correo'],
                'sexo' => $payload['sexo'],
                'tipo' => $tipo,
                'xnova_id' => $this->empresaId(),
                'active' => true,
                'es_por_defecto' => false,
            ]);

            if ($this->isCollaboratorType($tipo)) {
                Colaborador::query()->create([
                    'dni' => $socio->dni,
                    'puesto_id' => $payload['puesto_id'],
                    'sucursal_Id' => $payload['sucursal_Id'],
                    'xnova_id' => $this->empresaId(),
                ]);
            }

            $this->registrarLog($socio, 'Crear');

            return $socio->load($this->collaboratorRelationsForType($tipo));
        });

        return response()->json([
            'message' => $this->isCollaboratorType($tipo)
                ? 'Empleado creado correctamente.'
                : 'Cliente creado correctamente.',
            'socio' => $socio,
        ], 201);
    }

    public function show(SocioNegocio $socio): View
    {
        $this->asegurarSocioActual($socio);

        $tipo = $this->normalizeTipo($socio->tipo);
        $this->authorizeTipoAction($tipo, 'detalles');

        $socio->load([
            ...$this->collaboratorRelationsForType($tipo),
            'logs' => fn ($query) => $query->orderByDesc('Fecha'),
            'logs.usuarioModifica:id,name,email',
        ]);

        $usuariosAsignados = $this->isCollaboratorType($tipo)
            ? DB::table('users')->where('colaborador_dni', $socio->dni)->count()
            : 0;

        return view('socios.show', [
            'data' => $socio,
            'tipo' => $tipo,
            'isCollaborator' => $this->isCollaboratorType($tipo),
            'usuariosAsignados' => $usuariosAsignados,
            'canManageDefaultRecord' => $this->canManageDefaultRecord($socio),
        ]);
    }

    public function edit(SocioNegocio $socio): View
    {
        $this->asegurarSocioActual($socio);

        $tipo = $this->normalizeTipo($socio->tipo);
        $this->authorizeTipoAction($tipo, 'editar');
        $this->asegurarEdicionPermitida($socio);

        return $this->formView(
            $socio->load($this->collaboratorRelationsForType($tipo)),
            $tipo,
            true
        );
    }

    public function update(Request $request, SocioNegocio $socio): JsonResponse
    {
        $this->asegurarSocioActual($socio);

        $tipo = $this->normalizeTipo($socio->tipo);
        $this->authorizeTipoAction($tipo, 'editar');

        if ($response = $this->validarModificacionPermitida($socio)) {
            return $response;
        }

        $payload = $this->validatePayload($request, $tipo, $socio);
        $payload['dni'] = $socio->dni;

        $socio = DB::transaction(function () use ($payload, $tipo, $socio): SocioNegocio {
            $socio->update([
                'dni' => $payload['dni'],
                'nombres' => $payload['nombres'],
                'apellidos' => $payload['apellidos'],
                'fecha_nacimiento' => $payload['fecha_nacimiento'],
                'telefono' => $payload['telefono'],
                'correo' => $payload['correo'],
                'sexo' => $payload['sexo'],
            ]);

            if ($this->isCollaboratorType($tipo)) {
                Colaborador::query()->updateOrCreate(
                    ['dni' => $socio->dni],
                    [
                        'puesto_id' => $payload['puesto_id'],
                        'sucursal_Id' => $payload['sucursal_Id'],
                        'xnova_id' => $this->empresaId(),
                    ]
                );
            }

            $this->registrarLog($socio, 'Editar');

            return $socio->fresh()->load($this->collaboratorRelationsForType($tipo));
        });

        return response()->json([
            'message' => $this->isCollaboratorType($tipo)
                ? 'Empleado actualizado correctamente.'
                : 'Cliente actualizado correctamente.',
            'socio' => $socio,
        ]);
    }

    public function destroy(SocioNegocio $socio): JsonResponse
    {
        $this->asegurarSocioActual($socio);
        $this->authorizeTipoAction($this->normalizeTipo($socio->tipo), 'editar');

        if ($response = $this->validarModificacionPermitida($socio)) {
            return $response;
        }

        $socio->update([
            'active' => false,
        ]);

        $this->registrarLog($socio, 'Desactivar');

        return response()->json([
            'message' => $this->isCollaboratorType($socio->tipo)
                ? 'Empleado desactivado correctamente.'
                : 'Cliente desactivado correctamente.',
            'socio' => $socio->fresh(),
        ]);
    }

    public function activate(SocioNegocio $socio): JsonResponse
    {
        $this->asegurarSocioActual($socio);
        $this->authorizeTipoAction($this->normalizeTipo($socio->tipo), 'editar');

        if ($response = $this->validarModificacionPermitida($socio)) {
            return $response;
        }

        $socio->update([
            'active' => true,
        ]);

        $this->registrarLog($socio, 'Activar');

        return response()->json([
            'message' => $this->isCollaboratorType($socio->tipo)
                ? 'Empleado activado correctamente.'
                : 'Cliente activado correctamente.',
            'socio' => $socio->fresh(),
        ]);
    }

    private function dataByType(Request $request, string $tipo, string $viewPath): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $socios = $this->sociosBaseQuery($tipo, $search)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view($viewPath.'.rows', [
                'data' => $socios,
                'canManageDefaultRecords' => $this->permissionService->isCompanyCreator(Auth::user()),
            ])->render(),
            'cards' => view($viewPath.'.cards', [
                'data' => $socios,
                'canManageDefaultRecords' => $this->permissionService->isCompanyCreator(Auth::user()),
            ])->render(),
            'pagination' => view($viewPath.'.pagination', ['data' => $socios])->render(),
            'meta' => [
                'current_page' => $socios->currentPage(),
                'last_page' => $socios->lastPage(),
                'per_page' => $socios->perPage(),
                'total' => $socios->total(),
                'from' => $socios->firstItem(),
                'to' => $socios->lastItem(),
            ],
        ]);
    }

    private function sociosBaseQuery(string $tipo, string $search = '')
    {
        return SocioNegocio::query()
            ->select([
                'dni',
                'nombres',
                'apellidos',
                'telefono',
                'correo',
                'fecha_nacimiento',
                'sexo',
                'tipo',
                'active',
                'es_por_defecto',
                'created_at',
            ])
            ->with($this->collaboratorRelationsForType($tipo))
            ->where('tipo', $tipo)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%")
                        ->orWhere('correo', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('dni');
    }

    private function formView(SocioNegocio $data, string $tipo, bool $isEditing): View
    {
        $isCollaborator = $this->isCollaboratorType($tipo);

        return view('socios.form', [
            'data' => $data,
            'tipo' => $tipo,
            'isCollaborator' => $isCollaborator,
            'isEditing' => $isEditing,
            'colaborador' => $isCollaborator
                ? ($data->colaborador ?? new Colaborador([
                    'dni' => $data->dni,
                ]))
                : null,
            'puestos' => $isCollaborator ? $this->availablePuestos() : collect(),
            'sucursales' => $isCollaborator ? $this->availableSucursales() : collect(),
            'canManageDefaultRecord' => $this->canManageDefaultRecord($data),
        ]);
    }

    private function validatePayload(Request $request, string $tipo, ?SocioNegocio $socio = null): array
    {
        $xnovaId = $this->empresaId();
        $dniRule = Rule::unique('socio_negocios', 'dni');

        if ($socio) {
            $dniRule = $dniRule->ignore($socio->dni, 'dni');
        }

        $rules = [
            'tipo' => ['required', Rule::in([self::TYPE_CLIENT, self::TYPE_COLLABORATOR])],
            'dni' => ['required', 'string', 'max:25', $dniRule],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date'],
            'telefono' => ['required', 'string', 'max:25'],
            'correo' => ['nullable', 'string', 'email', 'max:150'],
            'sexo' => ['required', Rule::in(['M', 'F'])],
            'puesto_id' => [
                $this->isCollaboratorType($tipo) ? 'required' : 'nullable',
                'integer',
                Rule::exists('puestos', 'id')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
            'sucursal_Id' => [
                $this->isCollaboratorType($tipo) ? 'required' : 'nullable',
                'integer',
                Rule::exists('sucursales', 'id')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
        ];

        return $request->validate($rules, [
            'dni.required' => 'La identidad es obligatoria.',
            'dni.unique' => 'Ya existe un registro con esa identidad.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'telefono.required' => 'El telefono es obligatorio.',
            'correo.email' => 'El correo electronico no tiene un formato valido.',
            'sexo.required' => 'El sexo es obligatorio.',
            'puesto_id.required' => 'El puesto es obligatorio para empleados.',
            'sucursal_Id.required' => 'La sucursal es obligatoria para empleados.',
        ]);
    }

    private function availablePuestos()
    {
        return Puesto::query()
            ->select(['id', 'nombre'])
            ->where('active', true)
            ->orderBy('nombre')
            ->get();
    }

    private function availableSucursales()
    {
        return Sucursal::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();
    }

    private function collaboratorRelationsForType(string $tipo): array
    {
        if (! $this->isCollaboratorType($tipo)) {
            return [];
        }

        return [
            'colaborador',
            'colaborador.puesto:id,nombre',
            'colaborador.sucursal:id,nombre',
        ];
    }

    private function isCollaboratorType(string $tipo): bool
    {
        return $tipo === self::TYPE_COLLABORATOR;
    }

    private function normalizeTipo(string $tipo): string
    {
        $normalized = strtoupper(trim($tipo));

        if (! in_array($normalized, [self::TYPE_CLIENT, self::TYPE_COLLABORATOR], true)) {
            abort(404);
        }

        return $normalized;
    }

    private function authorizeTipoAction(string $tipo, string $action): void
    {
        $permissionCode = match ([$tipo, $action]) {
            [self::TYPE_CLIENT, 'crear'] => 'socios.clientes.crear',
            [self::TYPE_CLIENT, 'editar'] => 'socios.clientes.editar',
            [self::TYPE_CLIENT, 'detalles'] => 'socios.clientes.detalles',
            [self::TYPE_COLLABORATOR, 'crear'] => 'socios.colaboradores.crear',
            [self::TYPE_COLLABORATOR, 'editar'] => 'socios.colaboradores.editar',
            [self::TYPE_COLLABORATOR, 'detalles'] => 'socios.colaboradores.detalles',
            default => null,
        };

        if (! $permissionCode) {
            return;
        }

        if (! $this->permissionService->userHasPermission(Auth::user(), $permissionCode)) {
            abort(403, 'No tienes permiso para realizar esta accion.');
        }
    }

    private function asegurarSocioActual(SocioNegocio $socio): void
    {
        $this->authorize('view', $socio);
    }

    private function canManageDefaultRecord(SocioNegocio $socio): bool
    {
        return ! $socio->es_por_defecto || $this->permissionService->isCompanyCreator(Auth::user());
    }

    private function asegurarEdicionPermitida(SocioNegocio $socio): void
    {
        if ($this->canManageDefaultRecord($socio)) {
            return;
        }

        abort(403, 'Solo el dueno de la empresa puede modificar el registro por defecto.');
    }

    private function validarModificacionPermitida(SocioNegocio $socio): ?JsonResponse
    {
        if ($this->canManageDefaultRecord($socio)) {
            return null;
        }

        return response()->json([
            'message' => 'Solo el dueno de la empresa puede modificar el registro por defecto.',
        ], 422);
    }

    private function registrarLog(SocioNegocio $socio, string $accion): void
    {
        SocioLog::query()->updateOrCreate(
            [
                'Socio_Dni' => $socio->dni,
                'Accion' => $accion,
            ],
            [
                'Fecha' => now(),
                'User_Modifica_Id' => Auth::id(),
            ]
        );
    }
}

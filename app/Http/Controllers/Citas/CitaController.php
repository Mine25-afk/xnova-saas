<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Concerns\RespondeListados;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citas\CitaRequest;
use App\Http\Requests\Citas\ListadoCitasRequest;
use App\Models\Cita;
use App\Models\Estado;
use App\Models\User;
use App\Models\Xnova;
use App\Repositories\Citas\CitaRepository;
use App\Services\Citas\CitaService;
use App\Services\Citas\VistasRapidas;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitaController extends Controller
{
    use RespondeListados;

    public function __construct(
        private readonly CitaRepository $citas,
        private readonly CitaService $citaService,
        private readonly VistasRapidas $vistas,
    ) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $vistaActual = $this->vistas->resolver($request->query('vista'));
        $empresa = Xnova::query()->findOrFail($tenant->idOrFail());

        return view('citas.index', [
            'estados' => Estado::query()->whereIn('id', [
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_TERMINADA_ID,
                Cita::ESTADO_CANCELADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])->orderBy('id')->get(),
            'reservationUrl' => route('reservaciones.public.form', $empresa->reservacion_uid),
            'vistaActual' => $vistaActual,
            'quickViews' => $this->vistasRapidas($vistaActual),
        ]);
    }

    public function data(ListadoCitasRequest $request): JsonResponse
    {
        $vista = $request->vista();
        $citas = $this->citas->paginate(
            $this->citas->buscar($request->busqueda(), $request->estadoId(), $vista),
            $request->porPagina(),
        );

        $respuesta = $this->listado('citas.partials', 'data', $citas);
        $respuesta['meta']['vista'] = $vista;

        return response()->json($respuesta);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('citas.form', [
            'cita' => $this->citaService->borrador($user),
            'isEditing' => false,
        ]);
    }

    public function store(CitaRequest $request): JsonResponse
    {
        $cita = $this->citaService->crear($request->toDto());

        return response()->json([
            'message' => 'Cita creada y autorizada correctamente.',
            'cita' => $cita->load(CitaRepository::RELACIONES),
        ], 201);
    }

    public function show(Cita $cita): View
    {
        $this->authorize('view', $cita);

        $cita->load([
            'cliente:dni,nombres,apellidos,telefono',
            'colaborador:dni,puesto_id,sucursal_Id',
            'colaborador.socio:dni,nombres,apellidos,telefono',
            'colaborador.puesto:id,nombre',
            'sucursal:id,nombre,horario_id',
            'sucursal.horario:id,nombre',
            'producto:id,nombre,descripcion,precio',
            'estado:id,nombre',
        ]);

        return view('citas.show', ['cita' => $cita]);
    }

    public function edit(Cita $cita): View
    {
        $this->authorize('update', $cita);

        $cita->load([
            'cliente:dni,nombres,apellidos',
            'colaborador:dni,sucursal_Id',
            'colaborador.socio:dni,nombres,apellidos',
            'sucursal:id,nombre,horario_id',
            'producto:id,nombre,descripcion,precio',
            'estado:id,nombre',
        ]);

        return view('citas.form', [
            'cita' => $cita,
            'isEditing' => true,
        ]);
    }

    public function update(CitaRequest $request, Cita $cita): JsonResponse
    {
        $this->authorize('update', $cita);

        $this->citaService->actualizar($cita, $request->toDto());

        return response()->json([
            'message' => 'Cita actualizada correctamente.',
            'cita' => $cita->fresh()?->load(CitaRepository::RELACIONES),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vistasRapidas(string $vistaActual): array
    {
        $vistas = [];

        foreach ($this->vistas->definiciones() as $clave => $config) {
            $vistas[] = [
                ...$config,
                'key' => $clave,
                'href' => route('citas.listado', $clave === VistasRapidas::TODAS ? [] : ['vista' => $clave]),
                'is_active' => $vistaActual === $clave,
                'count' => $this->citas->contarVista($clave),
            ];
        }

        return $vistas;
    }
}

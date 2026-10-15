<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentXnova;
use App\Models\Cita;
use App\Models\Colaborador;
use App\Models\Estado;
use App\Models\HorarioDetalle;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\Xnova;
use App\Services\CitaNotificationService;
use App\Services\SuscripcionLimiteService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class CitaController extends Controller
{
    use ResolvesCurrentXnova;

    public function __construct(
        protected SuscripcionLimiteService $suscripcionLimiteService,
        protected CitaNotificationService $citaNotificationService
    ) {
    }

    public function listado(Request $request): View
    {
        $vistaActual = $this->resolveQuickView($request->query('vista'));

        return view('citas.index', [
            'estados' => $this->estadosCita(),
            'reservationUrl' => route('reservaciones.public.form', $this->reservationUid()),
            'vistaActual' => $vistaActual,
            'quickViews' => $this->quickViews($vistaActual),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'estado_id' => ['nullable', 'integer', Rule::in($this->estadoIdsCita())],
            'vista' => ['nullable', 'string', Rule::in(array_keys($this->quickViewDefinitions()))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $vista = $this->resolveQuickView($validated['vista'] ?? null);
        $estadoId = $this->effectiveEstadoIdForQuickView((int) ($validated['estado_id'] ?? 0), $vista);
        $perPage = (int) ($validated['per_page'] ?? 10);

        $citas = $this->citasBaseQuery($search, $estadoId, $vista)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('citas.partials.rows', ['data' => $citas])->render(),
            'cards' => view('citas.partials.cards', ['data' => $citas])->render(),
            'pagination' => view('citas.partials.pagination', ['data' => $citas])->render(),
            'meta' => [
                'current_page' => $citas->currentPage(),
                'last_page' => $citas->lastPage(),
                'per_page' => $citas->perPage(),
                'total' => $citas->total(),
                'from' => $citas->firstItem(),
                'to' => $citas->lastItem(),
                'vista' => $vista,
            ],
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();
        $defaultClient = SocioNegocio::query()
            ->select(['dni', 'nombres', 'apellidos'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('tipo', 'C')
            ->where('active', true)
            ->where('es_por_defecto', true)
            ->first();

        $defaultEmployee = Colaborador::query()
            ->with('socio:dni,nombres,apellidos,active,es_por_defecto')
            ->where('xnova_id', $this->currentXnovaId())
            ->whereHas('socio', function (Builder $query): void {
                $query
                    ->where('xnova_id', $this->currentXnovaId())
                    ->where('tipo', 'E')
                    ->where('active', true)
                    ->where('es_por_defecto', true);
            })
            ->first();

        $defaultBranch = Sucursal::query()
            ->select(['id', 'nombre'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('id', (int) ($user?->sucursal_Id ?? 0))
            ->first()
            ?? Sucursal::query()
                ->select(['id', 'nombre'])
                ->where('xnova_id', $this->currentXnovaId())
                ->orderBy('id')
                ->first();

        $cita = new Cita([
            'cliente_dni' => $defaultClient?->dni,
            'colaborador_dni' => $defaultEmployee?->dni,
            'sucursal_Id' => $defaultBranch?->id,
            'producto_id' => null,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '09:00',
            'hora_salida' => '10:00',
            'descripcion' => '',
            'comentario' => '',
            'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
        ]);

        if ($defaultClient) {
            $cita->setRelation('cliente', $defaultClient);
        }

        if ($defaultEmployee) {
            $cita->setRelation('colaborador', $defaultEmployee);
        }

        if ($defaultBranch) {
            $cita->setRelation('sucursal', $defaultBranch);
        }

        return view('citas.form', [
            'cita' => $cita,
            'isEditing' => false,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validarPayload($request);
        $this->suscripcionLimiteService->asegurarCupoPruebaCitasDisponible($this->currentXnovaId());

        if ($conflicto = $this->resolverConflictoColaborador($payload)) {
            return $conflicto;
        }

        if ($advertencia = $this->resolverAdvertenciaHorario($payload)) {
            return $advertencia;
        }

        $cita = Cita::query()->create([
            'cliente_dni' => $payload['cliente_dni'],
            'colaborador_dni' => $payload['colaborador_dni'],
            'sucursal_Id' => $payload['sucursal_Id'],
            'producto_id' => $payload['producto_id'] ?? null,
            'fecha' => $payload['fecha'],
            'hora_inicio' => $payload['hora_inicio'],
            'hora_salida' => $payload['hora_salida'],
            'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
            'descripcion' => $payload['descripcion'],
            'comentario' => $payload['comentario'] ?? null,
            'cliente_nombre_publico' => null,
            'cliente_identidad_publica' => null,
            'xnova_id' => $this->currentXnovaId(),
        ]);

        $this->citaNotificationService->sendCreatedNotification($cita);

        return response()->json([
            'message' => 'Cita creada y autorizada correctamente.',
            'cita' => $cita->load($this->relations()),
        ], 201);
    }

    public function show(Cita $cita): View
    {
        $this->asegurarCitaActual($cita);

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

        return view('citas.show', [
            'cita' => $cita,
        ]);
    }

    public function edit(Cita $cita): View
    {
        $this->asegurarCitaActual($cita);
        $this->asegurarCitaEditable($cita);

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

    public function update(Request $request, Cita $cita): JsonResponse
    {
        $this->asegurarCitaActual($cita);
        $this->asegurarCitaEditable($cita);

        $payload = $this->validarPayload($request, $cita);

        if ($conflicto = $this->resolverConflictoColaborador($payload, $cita)) {
            return $conflicto;
        }

        if ($advertencia = $this->resolverAdvertenciaHorario($payload)) {
            return $advertencia;
        }

        $previousSchedule = [
            'fecha' => optional($cita->fecha)->format('Y-m-d'),
            'hora_inicio' => substr((string) $cita->hora_inicio, 0, 5),
            'hora_salida' => substr((string) $cita->hora_salida, 0, 5),
        ];

        $cita->update([
            'cliente_dni' => $payload['cliente_dni'],
            'colaborador_dni' => $payload['colaborador_dni'],
            'sucursal_Id' => $payload['sucursal_Id'],
            'producto_id' => $payload['producto_id'] ?? null,
            'fecha' => $payload['fecha'],
            'hora_inicio' => $payload['hora_inicio'],
            'hora_salida' => $payload['hora_salida'],
            'descripcion' => $payload['descripcion'],
            'comentario' => $payload['comentario'] ?? null,
        ]);

        $scheduleChanged = $previousSchedule['fecha'] !== (string) $payload['fecha']
            || $previousSchedule['hora_inicio'] !== substr((string) $payload['hora_inicio'], 0, 5)
            || $previousSchedule['hora_salida'] !== substr((string) $payload['hora_salida'], 0, 5);

        if ($scheduleChanged) {
            $this->citaNotificationService->sendRescheduledNotification($cita, $previousSchedule);
        }

        return response()->json([
            'message' => 'Cita actualizada correctamente.',
            'cita' => $cita->fresh()->load($this->relations()),
        ]);
    }

    public function finish(Cita $cita): JsonResponse
    {
        $this->asegurarCitaActual($cita);

        if ($response = $this->validarCambioEstadoPermitido($cita)) {
            return $response;
        }

        $cita->update([
            'estado_id' => Cita::ESTADO_TERMINADA_ID,
        ]);

        return response()->json([
            'message' => 'Cita marcada como terminada.',
            'cita' => $cita->fresh()->load($this->relations()),
        ]);
    }

    public function cancel(Cita $cita): JsonResponse
    {
        $this->asegurarCitaActual($cita);

        if ($response = $this->validarCambioEstadoPermitido($cita)) {
            return $response;
        }

        $cita->update([
            'estado_id' => Cita::ESTADO_CANCELADA_ID,
        ]);

        $this->citaNotificationService->sendCancelledNotification($cita);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'cita' => $cita->fresh()->load($this->relations()),
        ]);
    }

    public function freeSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'colaborador_dni' => [
                'required',
                Rule::exists('colaboradores', 'dni')->where(fn ($query) => $query->where('xnova_id', $this->currentXnovaId())),
            ],
            'sucursal_Id' => [
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where(fn ($query) => $query->where('xnova_id', $this->currentXnovaId())),
            ],
            'fecha' => ['required', 'date', 'after_or_equal:'.now()->toDateString()],
            'duracion' => ['nullable', 'integer', 'min:15', 'max:240'],
        ]);

        $duracion = (int) ($validated['duracion'] ?? 60);
        $sucursal = Sucursal::query()
            ->select(['id', 'horario_id'])
            ->where('xnova_id', $this->currentXnovaId())
            ->find((int) $validated['sucursal_Id']);

        if (! $sucursal || (int) $sucursal->horario_id <= 0) {
            return response()->json([
                'slots' => [],
                'message' => 'La sucursal no tiene horario configurado para calcular huecos libres.',
            ]);
        }

        $fecha = Carbon::parse((string) $validated['fecha']);
        $detalle = HorarioDetalle::query()
            ->where('horario_id', $sucursal->horario_id)
            ->where('dia', $fecha->dayOfWeek)
            ->first();

        if (! $detalle || blank($detalle->hora_inicio) || blank($detalle->hora_salida)) {
            return response()->json([
                'slots' => [],
                'message' => 'No hay jornada disponible para ese dia en la sucursal seleccionada.',
            ]);
        }

        $inicioLaboral = $this->timeToMinutes((string) $detalle->hora_inicio);
        $salidaLaboral = $this->timeToMinutes((string) $detalle->hora_salida);
        $inicioReceso = filled($detalle->receso_incio) ? $this->timeToMinutes((string) $detalle->receso_incio) : null;
        $salidaReceso = filled($detalle->receso_salida) ? $this->timeToMinutes((string) $detalle->receso_salida) : null;

        $citasOcupadas = Cita::query()
            ->select(['hora_inicio', 'hora_salida'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('colaborador_dni', (string) $validated['colaborador_dni'])
            ->where('fecha', $fecha->toDateString())
            ->whereIn('estado_id', [
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn (Cita $cita): array => [
                'inicio' => $this->timeToMinutes((string) $cita->hora_inicio),
                'salida' => $this->timeToMinutes((string) $cita->hora_salida),
            ])
            ->values()
            ->all();

        $slots = [];

        for ($cursor = $inicioLaboral; ($cursor + $duracion) <= $salidaLaboral; $cursor += 15) {
            $slotEnd = $cursor + $duracion;

            $enReceso = $inicioReceso !== null && $salidaReceso !== null
                && $cursor < $salidaReceso
                && $slotEnd > $inicioReceso;

            if ($enReceso) {
                continue;
            }

            $hasOverlap = collect($citasOcupadas)->contains(
                fn (array $rango): bool => $cursor < $rango['salida'] && $slotEnd > $rango['inicio']
            );

            if ($hasOverlap) {
                continue;
            }

            $slots[] = [
                'inicio' => $this->minutesToTime($cursor),
                'salida' => $this->minutesToTime($slotEnd),
            ];
        }

        return response()->json([
            'slots' => $slots,
            'message' => empty($slots)
                ? 'No hay huecos disponibles para ese empleado en la fecha seleccionada.'
                : null,
        ]);
    }

    public function calendario(Request $request): View
    {
        $mes = $this->resolverMes($request->query('mes'));
        $inicioMes = $mes->startOfMonth();
        $finMes = $mes->endOfMonth();
        $inicioCalendario = $inicioMes->startOfWeek(Carbon::SUNDAY);
        $finCalendario = $finMes->endOfWeek(Carbon::SATURDAY);

        $citas = Cita::query()
            ->with([
                'cliente:dni,nombres,apellidos',
                'estado:id,nombre',
            ])
            ->where('xnova_id', $this->currentXnovaId())
            ->whereBetween('fecha', [$inicioCalendario->toDateString(), $finCalendario->toDateString()])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy(function (Cita $cita): string {
                return Carbon::parse((string) $cita->fecha)->format('Y-m-d');
            });

        $dias = [];
        $cursor = $inicioCalendario;

        while ($cursor->lte($finCalendario)) {
            $dias[] = [
                'fecha' => $cursor,
                'clave' => $cursor->format('Y-m-d'),
                'isCurrentMonth' => $cursor->month === $mes->month,
                'isToday' => $cursor->isToday(),
                'citas' => $citas->get($cursor->format('Y-m-d'), collect()),
            ];

            $cursor = $cursor->addDay();
        }

        return view('citas.calendar', [
            'mesActual' => $mes,
            'mesActualTexto' => $this->nombreMesEnEspanol($mes),
            'mesAnterior' => $mes->subMonth()->format('Y-m'),
            'mesSiguiente' => $mes->addMonth()->format('Y-m'),
            'semanasCalendario' => array_chunk($dias, 7),
            'diasSemana' => [
                ['corto' => 'Dom', 'nombre' => 'Domingo'],
                ['corto' => 'Lun', 'nombre' => 'Lunes'],
                ['corto' => 'Mar', 'nombre' => 'Martes'],
                ['corto' => 'Mie', 'nombre' => 'Miercoles'],
                ['corto' => 'Jue', 'nombre' => 'Jueves'],
                ['corto' => 'Vie', 'nombre' => 'Viernes'],
                ['corto' => 'Sab', 'nombre' => 'Sabado'],
            ],
        ]);
    }

    public function reportes(): View
    {
        return view('citas.placeholder', [
            'title' => 'Reportes de citas',
            'description' => 'Pronto mostraremos aqui los reportes operativos de citas.',
        ]);
    }

    protected function citasBaseQuery(string $search = '', int $estadoId = 0, string $vista = 'todas')
    {
        $query = Cita::query()
            ->select([
                'id',
                'cliente_dni',
                'colaborador_dni',
                'sucursal_Id',
                'producto_id',
                'fecha',
                'hora_inicio',
                'hora_salida',
                'estado_id',
                'descripcion',
                'comentario',
                'cliente_nombre_publico',
                'cliente_identidad_publica',
                'created_at',
            ])
            ->with($this->relations())
            ->where('xnova_id', $this->currentXnovaId())
            ->when($estadoId > 0, function (Builder $query) use ($estadoId): void {
                $query->where('estado_id', $estadoId);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('comentario', 'like', "%{$search}%")
                        ->orWhere('cliente_nombre_publico', 'like', "%{$search}%")
                        ->orWhere('cliente_identidad_publica', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function (Builder $clienteQuery) use ($search): void {
                            $clienteQuery
                                ->where('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%");
                        })
                        ->orWhereHas('colaborador.socio', function (Builder $colaboradorQuery) use ($search): void {
                            $colaboradorQuery
                                ->where('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%");
                        })
                        ->orWhereHas('producto', function (Builder $productoQuery) use ($search): void {
                            $productoQuery->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sucursal', function (Builder $sucursalQuery) use ($search): void {
                            $sucursalQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            });

        $this->applyQuickView($query, $vista);

        return $this->applyQuickViewOrder($query, $vista);
    }

    protected function validarPayload(Request $request, ?Cita $cita = null): array
    {
        $xnovaId = $this->currentXnovaId();
        $fechaMinima = now()->toDateString();
        $fechaMinimaTexto = Carbon::parse($fechaMinima)->format('d/m/Y');

        return $request->validate([
            'cliente_dni' => [
                'required',
                Rule::exists('socio_negocios', 'dni')->where(fn ($query) => $query
                    ->where('xnova_id', $xnovaId)
                    ->where('tipo', 'C')
                    ->where('active', true)),
            ],
            'colaborador_dni' => [
                'required',
                Rule::exists('colaboradores', 'dni')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
            'sucursal_Id' => [
                'required',
                'integer',
                Rule::exists('sucursales', 'id')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
            'producto_id' => [
                'nullable',
                'integer',
                Rule::exists('productos', 'id')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
            'fecha' => ['required', 'date', 'after_or_equal:'.$fechaMinima],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'descripcion' => ['required', 'string', 'max:255'],
            'comentario' => ['nullable', 'string', 'max:500'],
            'confirmar_fuera_horario' => ['nullable', 'boolean'],
        ], [
            'cliente_dni.required' => 'Debes seleccionar un cliente.',
            'cliente_dni.exists' => 'El cliente seleccionado no es valido.',
            'colaborador_dni.required' => 'Debes seleccionar un empleado.',
            'colaborador_dni.exists' => 'El empleado seleccionado no es valido.',
            'sucursal_Id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_Id.exists' => 'La sucursal seleccionada no es valida.',
            'producto_id.exists' => 'El producto seleccionado no es valido.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser menor a '.$fechaMinimaTexto.'.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la hora de inicio.',
            'descripcion.required' => 'La descripcion es obligatoria.',
        ]);
    }

    protected function resolverAdvertenciaHorario(array $payload): ?JsonResponse
    {
        if (! empty($payload['confirmar_fuera_horario'])) {
            return null;
        }

        $sucursal = Sucursal::query()
            ->select(['id', 'nombre', 'horario_id', 'xnova_id'])
            ->where('xnova_id', $this->currentXnovaId())
            ->find($payload['sucursal_Id']);

        if (! $sucursal || (int) $sucursal->horario_id <= 0) {
            return null;
        }

        $fecha = Carbon::parse($payload['fecha']);
        $detalle = HorarioDetalle::query()
            ->where('horario_id', $sucursal->horario_id)
            ->where('dia', $fecha->dayOfWeek)
            ->first();

        if (! $detalle || blank($detalle->hora_inicio) || blank($detalle->hora_salida)) {
            return $this->respuestaAdvertenciaHorario();
        }

        $horaInicio = $this->timeToMinutes($payload['hora_inicio']);
        $horaSalida = $this->timeToMinutes($payload['hora_salida']);
        $inicioLaboral = $this->timeToMinutes($detalle->hora_inicio);
        $salidaLaboral = $this->timeToMinutes($detalle->hora_salida);

        $fueraDeRango = $horaInicio < $inicioLaboral || $horaSalida > $salidaLaboral;

        $tocaReceso = false;

        if (filled($detalle->receso_incio) && filled($detalle->receso_salida)) {
            $inicioReceso = $this->timeToMinutes($detalle->receso_incio);
            $salidaReceso = $this->timeToMinutes($detalle->receso_salida);

            $tocaReceso = $horaInicio < $salidaReceso && $horaSalida > $inicioReceso;
        }

        if ($fueraDeRango || $tocaReceso) {
            return $this->respuestaAdvertenciaHorario();
        }

        return null;
    }

    protected function resolverConflictoColaborador(array $payload, ?Cita $citaActual = null): ?JsonResponse
    {
        $conflicto = Cita::query()
            ->select(['id', 'fecha', 'hora_inicio', 'hora_salida'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('colaborador_dni', $payload['colaborador_dni'])
            ->where('fecha', $payload['fecha'])
            ->whereIn('estado_id', [
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])
            ->when($citaActual?->exists, function (Builder $query) use ($citaActual): void {
                $query->whereKeyNot($citaActual->getKey());
            })
            ->where(function (Builder $query) use ($payload): void {
                $query
                    ->where('hora_inicio', '<', $payload['hora_salida'])
                    ->where('hora_salida', '>', $payload['hora_inicio']);
            })
            ->orderBy('hora_inicio')
            ->first();

        if (! $conflicto) {
            return null;
        }

        $fechaTexto = Carbon::parse($payload['fecha'])->format('d/m/Y');
        $horaInicio = substr((string) $conflicto->hora_inicio, 0, 5);
        $horaSalida = substr((string) $conflicto->hora_salida, 0, 5);
        $mensaje = "El colaborador seleccionado ya tiene una cita activa entre {$horaInicio} y {$horaSalida} el {$fechaTexto}.";

        return response()->json([
            'message' => $mensaje,
            'errors' => [
                'colaborador_dni' => [$mensaje],
                'hora_inicio' => [$mensaje],
                'hora_salida' => [$mensaje],
            ],
        ], 422);
    }

    protected function respuestaAdvertenciaHorario(): JsonResponse
    {
        return response()->json([
            'message' => 'Esta cita queda fuera del horario laboral configurado para la sucursal. Estas seguro de agregarla?',
            'requiere_confirmacion_fuera_horario' => true,
            'errors' => [
                'confirmar_fuera_horario' => [
                    'Esta cita queda fuera del horario laboral configurado para la sucursal.',
                ],
            ],
        ], 422);
    }

    protected function asegurarCitaActual(Cita $cita): void
    {
        abort_if((int) $cita->xnova_id !== $this->currentXnovaId(), 404);
    }

    protected function asegurarCitaEditable(Cita $cita): void
    {
        if ($cita->estaEditable()) {
            return;
        }

        abort(403, 'Solo las citas reservadas o autorizadas pueden editarse.');
    }

    protected function validarCambioEstadoPermitido(Cita $cita): ?JsonResponse
    {
        if ($cita->estaEditable()) {
            return null;
        }

        return response()->json([
            'message' => 'Solo las citas reservadas o autorizadas pueden cambiar de estado.',
        ], 422);
    }

    protected function estadoIdsCita(): array
    {
        return [
            Cita::ESTADO_RESERVADA_ID,
            Cita::ESTADO_TERMINADA_ID,
            Cita::ESTADO_CANCELADA_ID,
            Cita::ESTADO_AUTORIZADA_ID,
        ];
    }

    protected function estadosCita()
    {
        return Estado::query()
            ->whereIn('id', $this->estadoIdsCita())
            ->orderBy('id')
            ->get();
    }

    protected function relations(): array
    {
        return [
            'cliente:dni,nombres,apellidos',
            'colaborador:dni,sucursal_Id',
            'colaborador.socio:dni,nombres,apellidos',
            'sucursal:id,nombre',
            'producto:id,nombre,descripcion,precio',
            'estado:id,nombre',
        ];
    }

    protected function reservationUid(): string
    {
        return (string) Xnova::query()
            ->whereKey($this->currentXnovaId())
            ->value('reservacion_uid');
    }

    protected function quickViews(string $vistaActual): array
    {
        return collect($this->quickViewDefinitions())
            ->map(function (array $config, string $key) use ($vistaActual): array {
                $parameters = $key === 'todas' ? [] : ['vista' => $key];

                return [
                    ...$config,
                    'key' => $key,
                    'href' => route('citas.listado', $parameters),
                    'is_active' => $vistaActual === $key,
                    'count' => $this->countForQuickView($key),
                ];
            })
            ->values()
            ->all();
    }

    protected function quickViewDefinitions(): array
    {
        return [
            'todas' => [
                'title' => 'Todas las citas',
                'description' => 'Consulta toda la agenda y aplica filtros manuales segun la operacion que necesites.',
                'badge' => 'Vista general',
                'locked_estado_id' => 0,
                'only_today' => false,
            ],
            'por-autorizar' => [
                'title' => 'Por autorizar',
                'description' => 'Muestra las reservaciones pendientes para revisarlas y confirmarlas mas rapido.',
                'badge' => 'Pendientes',
                'locked_estado_id' => Cita::ESTADO_RESERVADA_ID,
                'only_today' => false,
            ],
            'hoy' => [
                'title' => 'Citas de hoy',
                'description' => 'Agrupa la agenda del dia para que el equipo vea lo que toca atender ahora.',
                'badge' => 'Hoy',
                'locked_estado_id' => 0,
                'only_today' => true,
            ],
        ];
    }

    protected function resolveQuickView(?string $vista): string
    {
        $vista = (string) $vista;

        return array_key_exists($vista, $this->quickViewDefinitions()) ? $vista : 'todas';
    }

    protected function effectiveEstadoIdForQuickView(int $estadoId, string $vista): int
    {
        $lockedEstadoId = (int) ($this->quickViewDefinitions()[$vista]['locked_estado_id'] ?? 0);

        return $lockedEstadoId > 0 ? $lockedEstadoId : $estadoId;
    }

    protected function countForQuickView(string $vista): int
    {
        $query = Cita::query()
            ->where('xnova_id', $this->currentXnovaId());

        $this->applyQuickView($query, $vista);

        return (int) $query->count();
    }

    protected function applyQuickView(Builder $query, string $vista): Builder
    {
        $config = $this->quickViewDefinitions()[$this->resolveQuickView($vista)] ?? $this->quickViewDefinitions()['todas'];
        $lockedEstadoId = (int) ($config['locked_estado_id'] ?? 0);

        if ($lockedEstadoId > 0) {
            $query->where('estado_id', $lockedEstadoId);
        }

        if (! empty($config['only_today'])) {
            $query->whereDate('fecha', now()->toDateString());
        }

        return $query;
    }

    protected function applyQuickViewOrder(Builder $query, string $vista): Builder
    {
        if (in_array($vista, ['por-autorizar', 'hoy'], true)) {
            return $query
                ->orderBy('fecha')
                ->orderBy('hora_inicio');
        }

        return $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio');
    }

    protected function resolverMes(?string $mes): CarbonImmutable
    {
        if (! filled($mes)) {
            return CarbonImmutable::now()->startOfMonth();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $mes)->startOfMonth();
        } catch (\Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }

    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }

    protected function minutesToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }

    protected function nombreMesEnEspanol(CarbonImmutable $mes): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return ($meses[(int) $mes->format('n')] ?? $mes->format('F')).' '.$mes->format('Y');
    }
}

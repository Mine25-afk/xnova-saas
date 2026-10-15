<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\Horario;
use App\Models\HorarioDetalle;
use App\Models\Sucursal;
use App\Models\SucursalLog;
use App\Services\SuscripcionLimiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class SucursalController extends Controller
{
    use UsaEmpresaActual;

    protected const DAY_LABELS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miercoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sabado',
    ];

    public function __construct(
        protected SuscripcionLimiteService $suscripcionLimiteService
    ) {
    }

    public function listado(): View
    {
        return view('sucursales.index');
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

        $sucursales = Sucursal::query()
            ->select(['id', 'nombre', 'created_at'])
            ->withCount(['users', 'colaboradores'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'rows' => view('sucursales.partials.rows', ['data' => $sucursales])->render(),
            'cards' => view('sucursales.partials.cards', ['data' => $sucursales])->render(),
            'pagination' => view('sucursales.partials.pagination', ['data' => $sucursales])->render(),
            'meta' => [
                'current_page' => $sucursales->currentPage(),
                'last_page' => $sucursales->lastPage(),
                'per_page' => $sucursales->perPage(),
                'total' => $sucursales->total(),
                'from' => $sucursales->firstItem(),
                'to' => $sucursales->lastItem(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('sucursales.form', [
            'sucursal' => new Sucursal([
                'horario_id' => 0,
            ]),
            'isEditing' => false,
            'scheduleRows' => $this->defaultScheduleRows(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validarSucursal($request);
        $this->suscripcionLimiteService->asegurarCupoSucursalesDisponible($this->empresaId());

        $scheduleRows = $data['schedule'];

        $sucursal = DB::transaction(function () use ($data, $scheduleRows): Sucursal {
            $sucursal = Sucursal::query()->create([
                'nombre' => $data['nombre'],
                'horario_id' => 0,
                'xnova_id' => $this->empresaId(),
            ]);

            $this->syncHorarioForSucursal($sucursal, $scheduleRows);
            $this->registrarLog($sucursal, 'Crear');

            return $sucursal->fresh();
        });

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'sucursal' => $sucursal,
        ], 201);
    }

    public function show(Sucursal $sucursal): View
    {
        $this->asegurarSucursalActual($sucursal);

        $sucursal->load([
            'horario.detalles',
            'logs' => fn ($query) => $query->orderByDesc('Fecha'),
            'logs.usuarioModifica:id,name,email',
        ])->loadCount(['users', 'colaboradores']);

        return view('sucursales.show', [
            'sucursal' => $sucursal,
            'scheduleRows' => $this->buildScheduleRows($sucursal),
        ]);
    }

    public function edit(Sucursal $sucursal): View
    {
        $this->asegurarSucursalActual($sucursal);

        return view('sucursales.form', [
            'sucursal' => $sucursal,
            'isEditing' => true,
            'scheduleRows' => $this->buildScheduleRows($sucursal),
        ]);
    }

    public function update(Request $request, Sucursal $sucursal): JsonResponse
    {
        $this->asegurarSucursalActual($sucursal);

        $data = $this->validarSucursal($request, $sucursal);

        $scheduleRows = $data['schedule'];

        DB::transaction(function () use ($sucursal, $data, $scheduleRows): void {
            $sucursal->update([
                'nombre' => $data['nombre'],
            ]);

            $this->syncHorarioForSucursal($sucursal, $scheduleRows);
            $this->registrarLog($sucursal, 'Editar');
        });

        return response()->json([
            'message' => 'Sucursal actualizada correctamente.',
            'sucursal' => $sucursal->fresh(),
        ]);
    }

    protected function validarSucursal(Request $request, ?Sucursal $sucursal = null): array
    {
        $rules = [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:sucursales,nombre,'.($sucursal?->id ?? 'NULL').',id,xnova_id,'.$this->empresaId(),
            ],
            'schedule' => ['required', 'array'],
        ];

        foreach (array_keys(self::DAY_LABELS) as $day) {
            $rules['schedule.'.$day.'.enabled'] = ['nullable', 'boolean'];
            $rules['schedule.'.$day.'.hora_inicio'] = ['nullable', 'date_format:H:i'];
            $rules['schedule.'.$day.'.hora_salida'] = ['nullable', 'date_format:H:i'];
            $rules['schedule.'.$day.'.receso_incio'] = ['nullable', 'date_format:H:i'];
            $rules['schedule.'.$day.'.receso_salida'] = ['nullable', 'date_format:H:i'];
        }

        $validated = $request->validate($rules, [
            'nombre.required' => 'El nombre de la sucursal es obligatorio.',
            'nombre.unique' => 'Ya existe una sucursal con ese nombre.',
            'schedule.required' => 'Debes definir el horario de la sucursal.',
        ]);

        $validated['schedule'] = $this->normalizeSchedulePayload($validated['schedule'] ?? []);

        return $validated;
    }

    protected function defaultScheduleRows(): array
    {
        return [
            0 => ['dia' => 0, 'nombre' => self::DAY_LABELS[0], 'enabled' => false, 'hora_inicio' => null, 'hora_salida' => null, 'receso_incio' => null, 'receso_salida' => null],
            1 => ['dia' => 1, 'nombre' => self::DAY_LABELS[1], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            2 => ['dia' => 2, 'nombre' => self::DAY_LABELS[2], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            3 => ['dia' => 3, 'nombre' => self::DAY_LABELS[3], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            4 => ['dia' => 4, 'nombre' => self::DAY_LABELS[4], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            5 => ['dia' => 5, 'nombre' => self::DAY_LABELS[5], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '17:00', 'receso_incio' => '12:00', 'receso_salida' => '13:00'],
            6 => ['dia' => 6, 'nombre' => self::DAY_LABELS[6], 'enabled' => true, 'hora_inicio' => '08:00', 'hora_salida' => '12:00', 'receso_incio' => null, 'receso_salida' => null],
        ];
    }

    protected function buildScheduleRows(Sucursal $sucursal): array
    {
        $rows = $this->defaultScheduleRows();

        if ((int) $sucursal->horario_id <= 0) {
            return $rows;
        }

        $detalles = HorarioDetalle::query()
            ->where('horario_id', $sucursal->horario_id)
            ->get()
            ->keyBy('dia');

        foreach ($rows as $day => $row) {
            $detalle = $detalles->get($day);

            if (! $detalle) {
                continue;
            }

            $horaInicio = blank($detalle->hora_inicio) ? null : substr((string) $detalle->hora_inicio, 0, 5);
            $horaSalida = blank($detalle->hora_salida) ? null : substr((string) $detalle->hora_salida, 0, 5);
            $recesoInicio = blank($detalle->receso_incio) ? null : substr((string) $detalle->receso_incio, 0, 5);
            $recesoSalida = blank($detalle->receso_salida) ? null : substr((string) $detalle->receso_salida, 0, 5);

            $rows[$day] = [
                'dia' => $day,
                'nombre' => self::DAY_LABELS[$day],
                'enabled' => filled($horaInicio) && filled($horaSalida),
                'hora_inicio' => $horaInicio,
                'hora_salida' => $horaSalida,
                'receso_incio' => $recesoInicio,
                'receso_salida' => $recesoSalida,
            ];
        }

        return $rows;
    }

    protected function normalizeSchedulePayload(array $schedule): array
    {
        $baseRows = $this->defaultScheduleRows();
        $normalized = [];
        $errors = [];
        $enabledCount = 0;

        foreach ($baseRows as $day => $defaultRow) {
            $row = $schedule[$day] ?? [];
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $horaInicio = $this->normalizeTimeValue($row['hora_inicio'] ?? null);
            $horaSalida = $this->normalizeTimeValue($row['hora_salida'] ?? null);
            $recesoInicio = $this->normalizeTimeValue($row['receso_incio'] ?? null);
            $recesoSalida = $this->normalizeTimeValue($row['receso_salida'] ?? null);

            if ($enabled) {
                $enabledCount++;

                if (blank($horaInicio)) {
                    $errors['schedule.'.$day.'.hora_inicio'][] = 'Define la hora de inicio para '.strtolower($defaultRow['nombre']).'.';
                }

                if (blank($horaSalida)) {
                    $errors['schedule.'.$day.'.hora_salida'][] = 'Define la hora de salida para '.strtolower($defaultRow['nombre']).'.';
                }

                if (filled($horaInicio) && filled($horaSalida) && $horaInicio >= $horaSalida) {
                    $errors['schedule.'.$day.'.hora_salida'][] = 'La hora de salida debe ser mayor a la hora de inicio en '.strtolower($defaultRow['nombre']).'.';
                }

                if (filled($recesoInicio) xor filled($recesoSalida)) {
                    $errors['schedule.'.$day.'.receso_incio'][] = 'Completa ambas horas de receso o deja ambas vacias en '.strtolower($defaultRow['nombre']).'.';
                }

                if (filled($recesoInicio) && filled($recesoSalida)) {
                    if ($recesoInicio >= $recesoSalida) {
                        $errors['schedule.'.$day.'.receso_salida'][] = 'El receso debe terminar despues de iniciar en '.strtolower($defaultRow['nombre']).'.';
                    }

                    if (filled($horaInicio) && filled($horaSalida) && ($recesoInicio < $horaInicio || $recesoSalida > $horaSalida)) {
                        $errors['schedule.'.$day.'.receso_incio'][] = 'El receso debe estar dentro del horario laboral de '.strtolower($defaultRow['nombre']).'.';
                    }
                }
            } else {
                $horaInicio = null;
                $horaSalida = null;
                $recesoInicio = null;
                $recesoSalida = null;
            }

            $normalized[$day] = [
                'dia' => $day,
                'nombre' => $defaultRow['nombre'],
                'enabled' => $enabled,
                'hora_inicio' => $horaInicio,
                'hora_salida' => $horaSalida,
                'receso_incio' => $recesoInicio,
                'receso_salida' => $recesoSalida,
            ];
        }

        if ($enabledCount === 0) {
            $errors['schedule'][] = 'Debes habilitar al menos un dia laboral.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    protected function normalizeTimeValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    protected function syncHorarioForSucursal(Sucursal $sucursal, array $scheduleRows): void
    {
        $horario = Horario::query()
            ->when((int) $sucursal->horario_id > 0, fn (Builder $query) => $query->whereKey($sucursal->horario_id))
            ->first();

        if (! $horario) {
            $horario = Horario::query()->create([
                'nombre' => 'Horario '.trim($sucursal->nombre),
                'active' => true,
                'xnova_id' => $sucursal->xnova_id,
            ]);
        } else {
            $horario->update([
                'nombre' => 'Horario '.trim($sucursal->nombre),
                'active' => true,
            ]);
        }

        foreach ($scheduleRows as $day => $row) {
            HorarioDetalle::query()->updateOrCreate(
                [
                    'horario_id' => $horario->id,
                    'dia' => $day,
                ],
                [
                    'nombre' => $row['nombre'],
                    'hora_inicio' => $row['hora_inicio'],
                    'hora_salida' => $row['hora_salida'],
                    'receso_incio' => $row['receso_incio'],
                    'receso_salida' => $row['receso_salida'],
                ],
            );
        }

        HorarioDetalle::query()
            ->where('horario_id', $horario->id)
            ->whereNotIn('dia', array_keys(self::DAY_LABELS))
            ->delete();

        if ((int) $sucursal->horario_id !== (int) $horario->id) {
            $sucursal->update([
                'horario_id' => $horario->id,
            ]);
        }
    }

    protected function registrarLog(Sucursal $sucursal, string $accion): void
    {
        SucursalLog::query()->updateOrCreate(
            [
                'Sucursal_Id' => $sucursal->id,
                'Accion' => $accion,
            ],
            [
                'Fecha' => now(),
                'User_Modifica_Id' => Auth::id(),
            ],
        );
    }

    protected function asegurarSucursalActual(Sucursal $sucursal): void
    {
        $this->authorize('view', $sucursal);
    }
}

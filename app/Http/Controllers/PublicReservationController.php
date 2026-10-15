<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Colaborador;
use App\Models\HorarioDetalle;
use App\Models\Producto;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\Xnova;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicReservationController extends Controller
{
    private const AUTH_MODE_GOOGLE = 'google';

    private const AUTH_MODE_GUEST = 'guest';

    public function show(string $uid): View
    {
        $xnova = $this->resolveCompany($uid);
        $colaboradores = $this->colaboradoresDisponibles($xnova->id);
        $singleCollaborator = $colaboradores->count() === 1 ? $colaboradores->first() : null;

        return view('citas.public-reservation', [
            'empresa' => $xnova,
            'productos' => $this->productosDisponibles($xnova->id),
            'sucursales' => $this->sucursalesDisponibles($xnova->id),
            'colaboradores' => $colaboradores,
            'singleCollaborator' => $singleCollaborator,
            'googleClientId' => (string) config('services.google.client_id'),
        ]);
    }

    public function store(Request $request, string $uid): RedirectResponse
    {
        $xnova = $this->resolveCompany($uid);
        $payload = $this->validatePayload($request, $xnova->id);

        $colaborador = $this->resolveCollaborator($xnova->id, (string) $payload['colaborador_dni']);

        if (! $colaborador || (int) ($colaborador->sucursal_Id ?? 0) <= 0) {
            return back()
                ->withErrors([
                    'colaborador_dni' => 'El colaborador seleccionado no tiene sucursal asignada.',
                ])
                ->withInput();
        }

        $payload['sucursal_Id'] = (int) $colaborador->sucursal_Id;

        $producto = Producto::query()
            ->where('xnova_id', $xnova->id)
            ->findOrFail($payload['producto_id']);

        $cliente = null;

        if ((string) $payload['auth_mode'] === self::AUTH_MODE_GUEST) {
            if ($error = $this->validateGuestDailyLimit($request, $xnova->id)) {
                return back()
                    ->withErrors([
                        'auth_mode' => $error,
                    ])
                    ->withInput();
            }

            $cliente = $this->resolveGuestClient($xnova->id, (string) $xnova->nombre);
        } else {
            $cliente = $this->resolveGoogleClient(
                $xnova->id,
                (string) $payload['google_email'],
                (string) ($payload['google_name'] ?? '')
            );
        }

        if ($conflicto = $this->findCollaboratorConflict($xnova->id, $payload)) {
            return back()
                ->withErrors([
                    'colaborador_dni' => $conflicto,
                    'hora_inicio' => $conflicto,
                    'hora_salida' => $conflicto,
                ])
                ->withInput();
        }

        if ($horario = $this->validateScheduleWithinBranchHours($xnova->id, $payload)) {
            return back()
                ->withErrors([
                    'hora_inicio' => $horario,
                    'hora_salida' => $horario,
                ])
                ->withInput();
        }

        $nombreCliente = trim(($cliente->nombres ?? '').' '.($cliente->apellidos ?? ''));
        $nombreCliente = $nombreCliente !== '' ? $nombreCliente : 'Cliente';

        $cita = Cita::query()->create([
            'cliente_dni' => $cliente->dni,
            'colaborador_dni' => $payload['colaborador_dni'],
            'sucursal_Id' => (int) $payload['sucursal_Id'],
            'producto_id' => $producto->id,
            'fecha' => $payload['fecha'],
            'hora_inicio' => $payload['hora_inicio'],
            'hora_salida' => $payload['hora_salida'],
            'estado_id' => Cita::ESTADO_RESERVADA_ID,
            'descripcion' => $producto->nombre,
            'comentario' => $payload['comentario'] ?? null,
            'cliente_nombre_publico' => $nombreCliente,
            'cliente_identidad_publica' => $cliente->dni,
            'xnova_id' => $xnova->id,
        ]);

        if ((string) $payload['auth_mode'] === self::AUTH_MODE_GUEST) {
            $this->markGuestReservationUsed($request, $xnova->id);
        }

        return redirect()
            ->route('reservaciones.public.form', $xnova->reservacion_uid)
            ->with('success', 'Reservacion enviada correctamente. Numero de cita: #'.$cita->id.'.');
    }

    protected function validatePayload(Request $request, int $xnovaId): array
    {
        $fechaMinima = now()->toDateString();

        return $request->validate([
            'auth_mode' => ['required', Rule::in([self::AUTH_MODE_GOOGLE, self::AUTH_MODE_GUEST])],
            'google_email' => ['required_if:auth_mode,'.self::AUTH_MODE_GOOGLE, 'nullable', 'string', 'email', 'max:150'],
            'google_name' => ['required_if:auth_mode,'.self::AUTH_MODE_GOOGLE, 'nullable', 'string', 'max:150'],
            'producto_id' => [
                'required',
                'integer',
                Rule::exists('productos', 'id')->where(fn ($query) => $query
                    ->where('xnova_id', $xnovaId)
                    ->where('active', true)),
            ],
            'colaborador_dni' => [
                'required',
                Rule::exists('colaboradores', 'dni')->where(fn ($query) => $query->where('xnova_id', $xnovaId)),
            ],
            'fecha' => ['required', 'date', 'after_or_equal:'.$fechaMinima],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'auth_mode.required' => 'Selecciona Google o invitado para continuar.',
            'google_email.required_if' => 'Debes proporcionar tu correo de Google.',
            'google_email.email' => 'El correo de Google no es valido.',
            'google_name.required_if' => 'Debes proporcionar tu nombre para Google.',
            'producto_id.required' => 'Debes seleccionar un producto.',
            'colaborador_dni.required' => 'Debes seleccionar un colaborador.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha no puede ser menor a la fecha actual.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la hora de inicio.',
        ]);
    }

    protected function resolveCompany(string $uid): Xnova
    {
        return Xnova::query()
            ->where('active', true)
            ->where('reservacion_uid', $uid)
            ->firstOrFail();
    }

    protected function productosDisponibles(int $xnovaId)
    {
        return Producto::query()
            ->select(['id', 'nombre', 'descripcion', 'precio', 'imagen_path'])
            ->where('xnova_id', $xnovaId)
            ->where('active', true)
            ->orderBy('nombre')
            ->get();
    }

    protected function sucursalesDisponibles(int $xnovaId)
    {
        return Sucursal::query()
            ->select(['id', 'nombre'])
            ->where('xnova_id', $xnovaId)
            ->orderBy('nombre')
            ->get();
    }

    protected function colaboradoresDisponibles(int $xnovaId)
    {
        return Colaborador::query()
            ->with('socio:dni,nombres,apellidos,active')
            ->with('sucursal:id,nombre')
            ->where('xnova_id', $xnovaId)
            ->whereHas('socio', function (Builder $query) use ($xnovaId): void {
                $query
                    ->where('xnova_id', $xnovaId)
                    ->where('tipo', 'E')
                    ->where('active', true);
            })
            ->whereNotNull('sucursal_Id')
            ->orderBy('dni')
            ->get();
    }

    protected function resolveCollaborator(int $xnovaId, string $dni): ?Colaborador
    {
        return Colaborador::query()
            ->select(['dni', 'sucursal_Id'])
            ->where('xnova_id', $xnovaId)
            ->where('dni', $dni)
            ->first();
    }

    protected function resolveGuestClient(int $xnovaId, string $companyName): SocioNegocio
    {
        $cliente = SocioNegocio::query()
            ->where('xnova_id', $xnovaId)
            ->where('tipo', 'C')
            ->where('active', true)
            ->where('es_por_defecto', true)
            ->first();

        if ($cliente) {
            return $cliente;
        }

        $fallbackClient = SocioNegocio::query()
            ->where('xnova_id', $xnovaId)
            ->where('tipo', 'C')
            ->where('active', true)
            ->orderBy('created_at')
            ->first();

        if ($fallbackClient) {
            return $fallbackClient;
        }

        $correo = 'cliente.general.'.(int) $xnovaId.'@xnova.local';
        $dni = $this->generateClientDni($xnovaId, 'GENCLI');

        return SocioNegocio::query()->create([
            'dni' => $dni,
            'nombres' => 'Cliente',
            'apellidos' => 'General',
            'fecha_nacimiento' => now()->subYears(18)->toDateString(),
            'telefono' => '0000-0000',
            'correo' => $correo,
            'sexo' => 'M',
            'tipo' => 'C',
            'xnova_id' => $xnovaId,
            'active' => true,
            'es_por_defecto' => true,
        ]);
    }

    protected function resolveGoogleClient(int $xnovaId, string $email, string $name): SocioNegocio
    {
        $normalizedEmail = Str::lower(trim($email));

        $existing = SocioNegocio::query()
            ->where('xnova_id', $xnovaId)
            ->where('tipo', 'C')
            ->whereRaw('LOWER(correo) = ?', [$normalizedEmail])
            ->first();

        if ($existing) {
            return $existing;
        }

        $cleanName = trim((string) preg_replace('/\s+/', ' ', $name));
        $parts = array_values(array_filter(explode(' ', $cleanName)));

        if (count($parts) <= 1) {
            $nombres = $parts[0] ?? 'Cliente';
            $apellidos = 'Google';
        } else {
            $apellidos = array_pop($parts);
            $nombres = implode(' ', $parts);
        }

        return SocioNegocio::query()->create([
            'dni' => $this->generateClientDni($xnovaId, 'CLIWEB'),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => now()->subYears(18)->toDateString(),
            'telefono' => '0000-0000',
            'correo' => $normalizedEmail,
            'sexo' => 'M',
            'tipo' => 'C',
            'xnova_id' => $xnovaId,
            'active' => true,
            'es_por_defecto' => false,
        ]);
    }

    protected function generateClientDni(int $xnovaId, string $prefix): string
    {
        do {
            $dni = Str::upper($prefix.$xnovaId.Str::random(6));
        } while (SocioNegocio::query()->whereKey($dni)->exists());

        return $dni;
    }

    protected function validateGuestDailyLimit(Request $request, int $xnovaId): ?string
    {
        $dateKey = now()->toDateString();
        $sessionKey = 'guest_reservation.'.$xnovaId.'.'.$dateKey;

        if ((bool) $request->session()->get($sessionKey, false)) {
            return 'Como invitado ya realizaste una reservacion hoy. Intenta nuevamente manana o usa Google.';
        }

        $ipLimitKey = $this->guestIpLimitKey($xnovaId, $dateKey, (string) $request->ip());

        if (Cache::has($ipLimitKey)) {
            return 'Como invitado ya existe una reservacion para hoy desde esta conexion. Intenta manana o usa Google.';
        }

        return null;
    }

    protected function markGuestReservationUsed(Request $request, int $xnovaId): void
    {
        $dateKey = now()->toDateString();
        $sessionKey = 'guest_reservation.'.$xnovaId.'.'.$dateKey;
        $request->session()->put($sessionKey, true);

        $ipLimitKey = $this->guestIpLimitKey($xnovaId, $dateKey, (string) $request->ip());
        $ttlSeconds = max(60, now()->diffInSeconds(now()->endOfDay()));

        Cache::put($ipLimitKey, true, $ttlSeconds);
    }

    protected function guestIpLimitKey(int $xnovaId, string $dateKey, string $ip): string
    {
        return 'guest-reservation:'.$xnovaId.':'.$dateKey.':'.md5($ip);
    }

    protected function findCollaboratorConflict(int $xnovaId, array $payload): ?string
    {
        $conflicto = Cita::query()
            ->select(['hora_inicio', 'hora_salida'])
            ->where('xnova_id', $xnovaId)
            ->where('colaborador_dni', $payload['colaborador_dni'])
            ->where('fecha', $payload['fecha'])
            ->whereIn('estado_id', [
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])
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

        return "El colaborador seleccionado ya tiene una cita activa entre {$horaInicio} y {$horaSalida} el {$fechaTexto}.";
    }

    protected function validateScheduleWithinBranchHours(int $xnovaId, array $payload): ?string
    {
        $sucursal = Sucursal::query()
            ->select(['id', 'horario_id'])
            ->where('xnova_id', $xnovaId)
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
            return 'La sucursal no tiene horario disponible para la fecha seleccionada.';
        }

        $horaInicio = $this->timeToMinutes($payload['hora_inicio']);
        $horaSalida = $this->timeToMinutes($payload['hora_salida']);
        $inicioLaboral = $this->timeToMinutes($detalle->hora_inicio);
        $salidaLaboral = $this->timeToMinutes($detalle->hora_salida);

        if ($horaInicio < $inicioLaboral || $horaSalida > $salidaLaboral) {
            return 'La cita queda fuera del horario laboral configurado para la sucursal.';
        }

        if (filled($detalle->receso_incio) && filled($detalle->receso_salida)) {
            $inicioReceso = $this->timeToMinutes($detalle->receso_incio);
            $salidaReceso = $this->timeToMinutes($detalle->receso_salida);

            if ($horaInicio < $salidaReceso && $horaSalida > $inicioReceso) {
                return 'La cita se cruza con el receso configurado para la sucursal.';
            }
        }

        return null;
    }

    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hours * 60) + $minutes;
    }
}

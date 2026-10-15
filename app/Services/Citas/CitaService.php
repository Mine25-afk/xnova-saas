<?php

namespace App\Services\Citas;

use App\DTOs\Citas\CitaData;
use App\Exceptions\BusinessRuleException;
use App\Models\Cita;
use App\Models\Colaborador;
use App\Models\SocioNegocio;
use App\Models\Sucursal;
use App\Models\User;
use App\Repositories\Citas\CitaRepository;
use App\Services\CitaNotificationService;
use App\Services\SuscripcionLimiteService;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ciclo de vida de una cita en la agenda interna: alta, reprogramación,
 * cierre y cancelación, con sus notificaciones por correo.
 */
final class CitaService
{
    public function __construct(
        private readonly CitaRepository $citas,
        private readonly AgendaService $agenda,
        private readonly SuscripcionLimiteService $limites,
        private readonly CitaNotificationService $notificaciones,
        private readonly TenantContext $tenant,
    ) {}

    /**
     * Las citas creadas por el equipo nacen autorizadas.
     */
    public function crear(CitaData $data): Cita
    {
        $this->limites->asegurarCupoPruebaCitasDisponible($this->tenant->idOrFail());
        $this->validarAgenda($data);

        $cita = $this->citas->create($data->atributos() + [
            'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
            'cliente_nombre_publico' => null,
            'cliente_identidad_publica' => null,
        ]);

        $this->notificaciones->sendCreatedNotification($cita);

        return $cita;
    }

    public function actualizar(Cita $cita, CitaData $data): Cita
    {
        $this->validarAgenda($data, (int) $cita->id);

        $horarioAnterior = [
            'fecha' => $cita->fecha->format('Y-m-d'),
            'hora_inicio' => substr((string) $cita->hora_inicio, 0, 5),
            'hora_salida' => substr((string) $cita->hora_salida, 0, 5),
        ];

        $this->citas->update($cita, $data->atributos());

        $reprogramada = $horarioAnterior['fecha'] !== $data->fecha
            || $horarioAnterior['hora_inicio'] !== substr($data->horaInicio, 0, 5)
            || $horarioAnterior['hora_salida'] !== substr($data->horaSalida, 0, 5);

        if ($reprogramada) {
            $this->notificaciones->sendRescheduledNotification($cita, $horarioAnterior);
        }

        return $cita;
    }

    public function terminar(Cita $cita): Cita
    {
        $this->asegurarCambioDeEstado($cita);

        return $this->citas->update($cita, ['estado_id' => Cita::ESTADO_TERMINADA_ID]);
    }

    public function cancelar(Cita $cita): Cita
    {
        $this->asegurarCambioDeEstado($cita);

        $this->citas->update($cita, ['estado_id' => Cita::ESTADO_CANCELADA_ID]);
        $this->notificaciones->sendCancelledNotification($cita);

        return $cita;
    }

    /**
     * Cita en blanco para el formulario: cliente y empleado por defecto y la
     * sucursal del usuario (o la primera de la empresa).
     */
    public function borrador(User $user): Cita
    {
        $cliente = SocioNegocio::query()
            ->select(['dni', 'nombres', 'apellidos'])
            ->where('tipo', SocioNegocio::TIPO_CLIENTE)
            ->where('active', true)
            ->where('es_por_defecto', true)
            ->first();

        $empleado = Colaborador::query()
            ->with('socio:dni,nombres,apellidos,active,es_por_defecto')
            ->whereHas('socio', fn (Builder $q) => $q->where('tipo', SocioNegocio::TIPO_COLABORADOR)->where('active', true)->where('es_por_defecto', true))
            ->first();

        $sucursal = Sucursal::query()->select(['id', 'nombre'])->find((int) ($user->sucursal_Id ?? 0))
            ?? Sucursal::query()->select(['id', 'nombre'])->orderBy('id')->first();

        $cita = new Cita([
            'cliente_dni' => $cliente?->dni,
            'colaborador_dni' => $empleado?->dni,
            'sucursal_Id' => $sucursal?->id,
            'producto_id' => null,
            'fecha' => now()->toDateString(),
            'hora_inicio' => '09:00',
            'hora_salida' => '10:00',
            'descripcion' => '',
            'comentario' => '',
            'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
        ]);

        foreach (['cliente' => $cliente, 'colaborador' => $empleado, 'sucursal' => $sucursal] as $relacion => $modelo) {
            if ($modelo !== null) {
                $cita->setRelation($relacion, $modelo);
            }
        }

        return $cita;
    }

    private function validarAgenda(CitaData $data, ?int $excluirCitaId = null): void
    {
        $this->agenda->asegurarSinConflicto($data->colaboradorDni, $data->fecha, $data->horaInicio, $data->horaSalida, $excluirCitaId);

        if ($data->confirmarFueraHorario) {
            return;
        }

        $evaluacion = $this->agenda->evaluarHorario($data->sucursalId, $data->fecha, $data->horaInicio, $data->horaSalida);

        if (! $evaluacion->permitida()) {
            throw new BusinessRuleException(
                'Esta cita queda fuera del horario laboral configurado para la sucursal. Estas seguro de agregarla?',
                ['confirmar_fuera_horario' => ['Esta cita queda fuera del horario laboral configurado para la sucursal.']],
                ['requiere_confirmacion_fuera_horario' => true],
            );
        }
    }

    private function asegurarCambioDeEstado(Cita $cita): void
    {
        if (! $cita->estaEditable()) {
            throw new BusinessRuleException('Solo las citas reservadas o autorizadas pueden cambiar de estado.');
        }
    }
}

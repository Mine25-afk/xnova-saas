<?php

namespace App\Services\Citas;

use App\Enums\EvaluacionHorario;
use App\Exceptions\BusinessRuleException;
use App\Models\Cita;
use App\Models\HorarioDetalle;
use App\Models\Sucursal;
use App\Repositories\Citas\CitaRepository;
use App\Support\Minutos;
use Carbon\Carbon;

/**
 * Reglas de agenda compartidas por la agenda interna y la reserva pública:
 * choques entre citas de un colaborador, horario laboral de la sucursal y
 * cálculo de huecos libres.
 */
final class AgendaService
{
    private const PASO_MINUTOS = 15;

    public function __construct(private readonly CitaRepository $citas) {}

    /**
     * Lanza 422 si el colaborador ya tiene una cita activa que se cruza con el rango.
     */
    public function asegurarSinConflicto(string $colaboradorDni, string $fecha, string $horaInicio, string $horaSalida, ?int $excluirCitaId = null): void
    {
        $conflicto = $this->citas->conflictoDeColaborador($colaboradorDni, $fecha, $horaInicio, $horaSalida, $excluirCitaId);

        if ($conflicto === null) {
            return;
        }

        $mensaje = sprintf(
            'El colaborador seleccionado ya tiene una cita activa entre %s y %s el %s.',
            substr((string) $conflicto->hora_inicio, 0, 5),
            substr((string) $conflicto->hora_salida, 0, 5),
            Carbon::parse($fecha)->format('d/m/Y'),
        );

        throw BusinessRuleException::enCampos(['colaborador_dni', 'hora_inicio', 'hora_salida'], $mensaje);
    }

    public function evaluarHorario(int $sucursalId, string $fecha, string $horaInicio, string $horaSalida): EvaluacionHorario
    {
        $sucursal = Sucursal::query()->select(['id', 'horario_id'])->find($sucursalId);

        if ($sucursal === null || (int) $sucursal->horario_id <= 0) {
            return EvaluacionHorario::SinHorarioConfigurado;
        }

        $jornada = $this->jornada((int) $sucursal->horario_id, $fecha);

        if ($jornada === null) {
            return EvaluacionHorario::SinJornada;
        }

        $inicio = Minutos::desde($horaInicio);
        $salida = Minutos::desde($horaSalida);

        if ($inicio < Minutos::desde((string) $jornada->hora_inicio) || $salida > Minutos::desde((string) $jornada->hora_salida)) {
            return EvaluacionHorario::FueraDeJornada;
        }

        if (filled($jornada->receso_incio) && filled($jornada->receso_salida)
            && Minutos::seSolapan($inicio, $salida, Minutos::desde((string) $jornada->receso_incio), Minutos::desde((string) $jornada->receso_salida))) {
            return EvaluacionHorario::EnReceso;
        }

        return EvaluacionHorario::Dentro;
    }

    /**
     * @return array{slots: list<array{inicio: string, salida: string}>, message: string|null}
     */
    public function huecosLibres(string $colaboradorDni, int $sucursalId, string $fecha, int $duracion): array
    {
        $sucursal = Sucursal::query()->select(['id', 'horario_id'])->find($sucursalId);

        if ($sucursal === null || (int) $sucursal->horario_id <= 0) {
            return ['slots' => [], 'message' => 'La sucursal no tiene horario configurado para calcular huecos libres.'];
        }

        $jornada = $this->jornada((int) $sucursal->horario_id, $fecha);

        if ($jornada === null) {
            return ['slots' => [], 'message' => 'No hay jornada disponible para ese dia en la sucursal seleccionada.'];
        }

        $ocupadas = $this->citas->activasDelColaborador($colaboradorDni, $fecha)
            ->map(fn (Cita $cita): array => [
                'inicio' => Minutos::desde((string) $cita->hora_inicio),
                'salida' => Minutos::desde((string) $cita->hora_salida),
            ])
            ->values()
            ->all();

        $slots = $this->calcularHuecos(
            Minutos::desde((string) $jornada->hora_inicio),
            Minutos::desde((string) $jornada->hora_salida),
            filled($jornada->receso_incio) ? Minutos::desde((string) $jornada->receso_incio) : null,
            filled($jornada->receso_salida) ? Minutos::desde((string) $jornada->receso_salida) : null,
            $ocupadas,
            $duracion,
        );

        return [
            'slots' => $slots,
            'message' => $slots === [] ? 'No hay huecos disponibles para ese empleado en la fecha seleccionada.' : null,
        ];
    }

    /**
     * Huecos de $duracion minutos cada 15 minutos dentro de la jornada, sin
     * tocar el receso ni las citas ya ocupadas. Lógica pura (sin base de datos).
     *
     * @param  list<array{inicio: int, salida: int}>  $ocupadas
     * @return list<array{inicio: string, salida: string}>
     */
    public function calcularHuecos(int $inicioJornada, int $finJornada, ?int $inicioReceso, ?int $finReceso, array $ocupadas, int $duracion): array
    {
        $huecos = [];

        for ($cursor = $inicioJornada; $cursor + $duracion <= $finJornada; $cursor += self::PASO_MINUTOS) {
            $fin = $cursor + $duracion;

            if ($inicioReceso !== null && $finReceso !== null && Minutos::seSolapan($cursor, $fin, $inicioReceso, $finReceso)) {
                continue;
            }

            foreach ($ocupadas as $rango) {
                if (Minutos::seSolapan($cursor, $fin, $rango['inicio'], $rango['salida'])) {
                    continue 2;
                }
            }

            $huecos[] = ['inicio' => Minutos::aHora($cursor), 'salida' => Minutos::aHora($fin)];
        }

        return $huecos;
    }

    private function jornada(int $horarioId, string $fecha): ?HorarioDetalle
    {
        $detalle = HorarioDetalle::query()
            ->where('horario_id', $horarioId)
            ->where('dia', Carbon::parse($fecha)->dayOfWeek)
            ->first();

        if ($detalle === null || blank($detalle->hora_inicio) || blank($detalle->hora_salida)) {
            return null;
        }

        return $detalle;
    }
}

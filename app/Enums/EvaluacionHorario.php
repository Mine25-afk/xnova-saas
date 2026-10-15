<?php

namespace App\Enums;

/**
 * Resultado de comparar una cita contra el horario laboral de la sucursal.
 */
enum EvaluacionHorario
{
    /** La sucursal no tiene horario configurado: no hay nada contra qué validar. */
    case SinHorarioConfigurado;

    /** La cita cae dentro de la jornada y fuera del receso. */
    case Dentro;

    /** La sucursal no trabaja ese día de la semana. */
    case SinJornada;

    case FueraDeJornada;

    case EnReceso;

    public function permitida(): bool
    {
        return $this === self::Dentro || $this === self::SinHorarioConfigurado;
    }

    /**
     * Mensaje para la reserva pública, donde fuera de horario se rechaza.
     */
    public function mensajePublico(): ?string
    {
        return match ($this) {
            self::SinJornada => 'La sucursal no tiene horario disponible para la fecha seleccionada.',
            self::FueraDeJornada => 'La cita queda fuera del horario laboral configurado para la sucursal.',
            self::EnReceso => 'La cita se cruza con el receso configurado para la sucursal.',
            default => null,
        };
    }
}

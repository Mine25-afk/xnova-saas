<?php

namespace App\Support;

/**
 * Conversión entre horas "HH:MM[:SS]" y minutos desde la medianoche.
 */
final class Minutos
{
    public static function desde(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', substr($hora, 0, 5)) + [0, 0]);

        return ($horas * 60) + $minutos;
    }

    public static function aHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    /**
     * ¿Se cruzan los rangos [inicioA, finA) y [inicioB, finB)?
     */
    public static function seSolapan(int $inicioA, int $finA, int $inicioB, int $finB): bool
    {
        return $inicioA < $finB && $finA > $inicioB;
    }
}

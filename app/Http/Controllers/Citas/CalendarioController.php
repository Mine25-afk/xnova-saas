<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Repositories\Citas\CitaRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Calendario mensual de citas (semanas de domingo a sábado).
 */
class CalendarioController extends Controller
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const DIAS_SEMANA = [
        ['corto' => 'Dom', 'nombre' => 'Domingo'],
        ['corto' => 'Lun', 'nombre' => 'Lunes'],
        ['corto' => 'Mar', 'nombre' => 'Martes'],
        ['corto' => 'Mie', 'nombre' => 'Miercoles'],
        ['corto' => 'Jue', 'nombre' => 'Jueves'],
        ['corto' => 'Vie', 'nombre' => 'Viernes'],
        ['corto' => 'Sab', 'nombre' => 'Sabado'],
    ];

    public function __invoke(Request $request, CitaRepository $citas): View
    {
        $mes = $this->mes($request->query('mes'));
        $inicio = $mes->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $fin = $mes->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $porDia = $citas->entreFechas($inicio->toDateString(), $fin->toDateString())
            ->groupBy(fn (Cita $cita): string => $cita->fecha->format('Y-m-d'));

        $dias = [];

        for ($cursor = $inicio; $cursor->lte($fin); $cursor = $cursor->addDay()) {
            $dias[] = [
                'fecha' => $cursor,
                'clave' => $cursor->format('Y-m-d'),
                'isCurrentMonth' => $cursor->month === $mes->month,
                'isToday' => $cursor->isToday(),
                'citas' => $porDia->get($cursor->format('Y-m-d'), collect()),
            ];
        }

        return view('citas.calendar', [
            'mesActual' => $mes,
            'mesActualTexto' => self::MESES[$mes->month].' '.$mes->format('Y'),
            'mesAnterior' => $mes->subMonth()->format('Y-m'),
            'mesSiguiente' => $mes->addMonth()->format('Y-m'),
            'semanasCalendario' => array_chunk($dias, 7),
            'diasSemana' => self::DIAS_SEMANA,
        ]);
    }

    private function mes(mixed $mes): CarbonImmutable
    {
        if (! is_string($mes) || $mes === '') {
            return CarbonImmutable::now()->startOfMonth();
        }

        try {
            // "!" reinicia el día a 1: sin él, "2026-02" un día 30 se desborda a marzo.
            $fecha = CarbonImmutable::createFromFormat('!Y-m', $mes);
        } catch (Throwable) {
            $fecha = null;
        }

        return $fecha instanceof CarbonImmutable ? $fecha->startOfMonth() : CarbonImmutable::now()->startOfMonth();
    }
}

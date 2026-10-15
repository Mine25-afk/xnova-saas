<?php

use App\Enums\EvaluacionHorario;
use App\Repositories\Citas\CitaRepository;
use App\Services\Citas\AgendaService;
use App\Support\Minutos;

beforeEach(function () {
    $this->agenda = new AgendaService(Mockery::mock(CitaRepository::class));
});

it('convierte horas a minutos y de vuelta', function () {
    expect(Minutos::desde('08:30'))->toBe(510)
        ->and(Minutos::desde('13:45:00'))->toBe(825)
        ->and(Minutos::aHora(510))->toBe('08:30');
});

it('detecta solapes de rangos sin contar los bordes', function () {
    expect(Minutos::seSolapan(540, 600, 570, 630))->toBeTrue()
        ->and(Minutos::seSolapan(540, 600, 600, 660))->toBeFalse();
});

it('genera huecos cada 15 minutos sin tocar receso ni citas ocupadas', function () {
    $huecos = $this->agenda->calcularHuecos(
        Minutos::desde('08:00'),
        Minutos::desde('11:00'),
        Minutos::desde('09:30'),
        Minutos::desde('10:00'),
        [['inicio' => Minutos::desde('10:00'), 'salida' => Minutos::desde('10:30')]],
        30,
    );

    expect(array_column($huecos, 'inicio'))->toBe(['08:00', '08:15', '08:30', '08:45', '09:00', '10:30']);
});

it('no genera huecos si la duración no cabe en la jornada', function () {
    expect($this->agenda->calcularHuecos(480, 510, null, null, [], 60))->toBe([]);
});

it('solo permite citas dentro de horario o en sucursales sin horario', function () {
    expect(EvaluacionHorario::Dentro->permitida())->toBeTrue()
        ->and(EvaluacionHorario::SinHorarioConfigurado->permitida())->toBeTrue()
        ->and(EvaluacionHorario::EnReceso->permitida())->toBeFalse()
        ->and(EvaluacionHorario::FueraDeJornada->mensajePublico())->toContain('fuera del horario')
        ->and(EvaluacionHorario::Dentro->mensajePublico())->toBeNull();
});

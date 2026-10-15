<?php

use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Escenario;

beforeEach(function () {
    Mail::fake();

    $this->e = Escenario::crear();
    $this->lunes = now()->next(Carbon::MONDAY)->toDateString();
});

function datosCita(object $e, string $fecha, array $extra = []): array
{
    return array_merge([
        'cliente_dni' => $e->cliente->dni,
        'colaborador_dni' => $e->colaborador->dni,
        'sucursal_Id' => $e->sucursal->id,
        'producto_id' => $e->producto->id,
        'fecha' => $fecha,
        'hora_inicio' => '09:00',
        'hora_salida' => '10:00',
        'descripcion' => 'Corte de cabello',
    ], $extra);
}

it('crea una cita autorizada dentro del horario', function () {
    $this->actingAs($this->e->user)
        ->postJson(route('citas.store'), datosCita($this->e, $this->lunes))
        ->assertCreated()
        ->assertJsonPath('cita.estado_id', Cita::ESTADO_AUTORIZADA_ID);

    $this->assertDatabaseHas('citas', [
        'xnova_id' => $this->e->xnova->id,
        'colaborador_dni' => $this->e->colaborador->dni,
        'estado_id' => Cita::ESTADO_AUTORIZADA_ID,
    ]);
});

it('rechaza una cita que choca con otra del mismo colaborador', function () {
    $this->actingAs($this->e->user);

    $this->postJson(route('citas.store'), datosCita($this->e, $this->lunes))->assertCreated();

    $this->postJson(route('citas.store'), datosCita($this->e, $this->lunes, ['hora_inicio' => '09:30', 'hora_salida' => '10:30']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['colaborador_dni', 'hora_inicio', 'hora_salida']);
});

it('pide confirmación fuera del horario laboral y acepta la cita confirmada', function () {
    $this->actingAs($this->e->user);

    $fueraDeHorario = datosCita($this->e, $this->lunes, ['hora_inicio' => '12:15', 'hora_salida' => '12:45']);

    $this->postJson(route('citas.store'), $fueraDeHorario)
        ->assertUnprocessable()
        ->assertJsonPath('requiere_confirmacion_fuera_horario', true);

    $this->postJson(route('citas.store'), $fueraDeHorario + ['confirmar_fuera_horario' => true])
        ->assertCreated();
});

it('cancela una cita y no permite cambiarla de estado otra vez', function () {
    $cita = Cita::factory()->create([
        'xnova_id' => $this->e->xnova->id,
        'cliente_dni' => $this->e->cliente->dni,
        'colaborador_dni' => $this->e->colaborador->dni,
        'sucursal_Id' => $this->e->sucursal->id,
        'fecha' => $this->lunes,
    ]);

    $this->actingAs($this->e->user);

    $this->patchJson(route('citas.cancel', $cita->id))->assertOk();
    $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado_id' => Cita::ESTADO_CANCELADA_ID]);

    $this->patchJson(route('citas.finish', $cita->id))->assertUnprocessable();
});

it('calcula huecos libres sin el receso ni las citas ocupadas', function () {
    Cita::factory()->create([
        'xnova_id' => $this->e->xnova->id,
        'cliente_dni' => $this->e->cliente->dni,
        'colaborador_dni' => $this->e->colaborador->dni,
        'sucursal_Id' => $this->e->sucursal->id,
        'fecha' => $this->lunes,
        'hora_inicio' => '08:00',
        'hora_salida' => '09:00',
    ]);

    $slots = $this->actingAs($this->e->user)
        ->getJson(route('citas.free-slots', [
            'colaborador_dni' => $this->e->colaborador->dni,
            'sucursal_Id' => $this->e->sucursal->id,
            'fecha' => $this->lunes,
            'duracion' => 60,
        ]))
        ->assertOk()
        ->json('slots');

    $inicios = array_column($slots, 'inicio');

    expect($inicios)->not->toContain('08:00')
        ->not->toContain('11:30')
        ->not->toContain('12:00')
        ->toContain('09:00')
        ->toContain('13:00')
        ->toContain('16:00');
});

it('permite reservar como invitado desde el formulario público una vez al día', function () {
    $datos = [
        'auth_mode' => 'guest',
        'producto_id' => $this->e->producto->id,
        'colaborador_dni' => $this->e->colaborador->dni,
        'fecha' => $this->lunes,
        'hora_inicio' => '10:00',
        'hora_salida' => '11:00',
    ];

    $url = route('reservaciones.public.store', $this->e->xnova->reservacion_uid);

    $this->post($url, $datos)->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('citas', [
        'xnova_id' => $this->e->xnova->id,
        'estado_id' => Cita::ESTADO_RESERVADA_ID,
        'hora_inicio' => '10:00',
    ]);

    $this->post($url, array_merge($datos, ['hora_inicio' => '14:00', 'hora_salida' => '15:00']))
        ->assertSessionHasErrors('auth_mode');
});

it('rechaza en la reserva pública horarios fuera de la jornada', function () {
    $this->post(route('reservaciones.public.store', $this->e->xnova->reservacion_uid), [
        'auth_mode' => 'guest',
        'producto_id' => $this->e->producto->id,
        'colaborador_dni' => $this->e->colaborador->dni,
        'fecha' => $this->lunes,
        'hora_inicio' => '18:00',
        'hora_salida' => '19:00',
    ])->assertSessionHasErrors(['hora_inicio', 'hora_salida']);

    $this->assertDatabaseCount('citas', 0);
});

it('responde 404 a un identificador público inexistente', function () {
    $this->get(route('reservaciones.public.form', 'no-existe'))->assertNotFound();
});

it('la reserva pública no acepta productos de otra empresa', function () {
    $otra = Escenario::crear();

    $this->post(route('reservaciones.public.store', $this->e->xnova->reservacion_uid), [
        'auth_mode' => 'guest',
        'producto_id' => $otra->producto->id,
        'colaborador_dni' => $this->e->colaborador->dni,
        'fecha' => $this->lunes,
        'hora_inicio' => '10:00',
        'hora_salida' => '11:00',
    ])->assertSessionHasErrors('producto_id');
});

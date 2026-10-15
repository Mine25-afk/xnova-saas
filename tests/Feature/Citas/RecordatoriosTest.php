<?php

use App\Mail\CitaLifecycleMail;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Escenario;

it('envía recordatorios de 24h a las citas de todas las empresas, cada una en su tenant', function () {
    Mail::fake();
    $this->travelTo(Carbon::parse('2026-11-09 08:00:00'));

    foreach ([Escenario::crear(), Escenario::crear()] as $e) {
        Cita::factory()->create([
            'xnova_id' => $e->xnova->id,
            'cliente_dni' => $e->cliente->dni,
            'colaborador_dni' => $e->colaborador->dni,
            'sucursal_Id' => $e->sucursal->id,
            'fecha' => '2026-11-10',
            'hora_inicio' => '08:00',
            'hora_salida' => '09:00',
        ]);
    }

    $this->artisan('citas:enviar-recordatorios')->assertSuccessful();

    Mail::assertSent(CitaLifecycleMail::class, 2);
    expect(Cita::query()->acrossTenants()->whereNotNull('recordatorio_24h_at')->count())->toBe(2);
});

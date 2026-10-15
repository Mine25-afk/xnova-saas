<?php

use App\Services\CitaNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('citas:enviar-recordatorios', function (CitaNotificationService $service) {
    $summary = $service->sendDueReminders();

    $this->info('Recordatorios 24h enviados: '.(int) ($summary['recordatorio_24h'] ?? 0));
    $this->info('Recordatorios 2h enviados: '.(int) ($summary['recordatorio_2h'] ?? 0));
})->purpose('Enviar recordatorios de citas por correo para ventanas de 24h y 2h');

Schedule::command('citas:enviar-recordatorios')->everyMinute();

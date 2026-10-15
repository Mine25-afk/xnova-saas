<?php

namespace App\Services;

use App\Mail\CitaLifecycleMail;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CitaNotificationService
{
    public function sendCreatedNotification(Cita $cita): void
    {
        $cita->loadMissing($this->relations());
        $companyName = $this->companyName($cita);

        $this->sendLifecycleMail($cita, [
            'subject' => 'Tu cita en '.$companyName.' fue autorizada',
            'title' => 'Tu cita fue autorizada',
            'companyName' => $companyName,
            'intro' => $companyName.' ha autorizado tu cita. Aqui te compartimos los detalles para que la tengas a mano.',
            'detailLines' => $this->baseDetails($cita),
            'footer' => 'Si necesitas reprogramarla o cancelarla, puedes comunicarte con el equipo de '.$companyName.'.',
        ]);

        $cita->forceFill([
            'notificacion_creada_at' => now(),
        ])->save();
    }

    public function sendRescheduledNotification(Cita $cita, array $previousSchedule): void
    {
        $cita->loadMissing($this->relations());
        $companyName = $this->companyName($cita);

        $previousDate = $this->formatDate($previousSchedule['fecha'] ?? null);
        $previousStart = (string) ($previousSchedule['hora_inicio'] ?? '');
        $previousEnd = (string) ($previousSchedule['hora_salida'] ?? '');

        $details = [
            'Horario anterior: '.$previousDate.' '.$previousStart.' - '.$previousEnd,
            'Horario nuevo: '.$this->formatDate(optional($cita->fecha)->format('Y-m-d')).' '.substr((string) $cita->hora_inicio, 0, 5).' - '.substr((string) $cita->hora_salida, 0, 5),
            ...$this->baseDetails($cita),
        ];

        $this->sendLifecycleMail($cita, [
            'subject' => 'Tu cita en '.$companyName.' fue reprogramada',
            'title' => 'Tu cita fue reprogramada',
            'companyName' => $companyName,
            'intro' => $companyName.' ha reprogramado tu cita. Revisa el nuevo horario para que puedas organizarte con tiempo.',
            'detailLines' => $details,
            'footer' => 'Si el nuevo horario no te funciona, responde a este correo o comunicate con '.$companyName.'.',
        ]);

        $cita->forceFill([
            'notificacion_reprogramada_at' => now(),
            'recordatorio_24h_at' => null,
            'recordatorio_2h_at' => null,
        ])->save();
    }

    public function sendCancelledNotification(Cita $cita): void
    {
        $cita->loadMissing($this->relations());
        $companyName = $this->companyName($cita);

        $this->sendLifecycleMail($cita, [
            'subject' => 'Tu cita en '.$companyName.' fue cancelada',
            'title' => 'Tu cita fue cancelada',
            'companyName' => $companyName,
            'intro' => $companyName.' ha cancelado tu cita. Te compartimos la informacion del registro por si necesitas reagendarla.',
            'detailLines' => $this->baseDetails($cita),
            'footer' => 'Si deseas una nueva fecha, el equipo de '.$companyName.' puede ayudarte a reagendar.',
        ]);

        $cita->forceFill([
            'notificacion_cancelada_at' => now(),
        ])->save();
    }

    public function sendDueReminders(): array
    {
        $sent24h = $this->sendReminderWindow(24, 'recordatorio_24h_at', 'Recordatorio de cita (24 horas antes)');
        $sent2h = $this->sendReminderWindow(2, 'recordatorio_2h_at', 'Recordatorio de cita (2 horas antes)');

        return [
            'recordatorio_24h' => $sent24h,
            'recordatorio_2h' => $sent2h,
        ];
    }

    protected function sendReminderWindow(int $hoursBefore, string $column, string $subject): int
    {
        $windowStart = now()->addHours($hoursBefore);
        $windowEnd = $windowStart->copy()->addMinutes(1);

        $candidateDates = [$windowStart->toDateString()];

        if ($windowEnd->toDateString() !== $windowStart->toDateString()) {
            $candidateDates[] = $windowEnd->toDateString();
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Cita> $citas */
        $citas = Cita::query()
            ->with($this->relations())
            ->whereIn('estado_id', [
                Cita::ESTADO_RESERVADA_ID,
                Cita::ESTADO_AUTORIZADA_ID,
            ])
            ->whereNull($column)
            ->whereIn('fecha', $candidateDates)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $sentCount = 0;

        foreach ($citas as $cita) {
            if (! $cita instanceof Cita) {
                continue;
            }

            $startDateTime = Carbon::parse(optional($cita->fecha)->format('Y-m-d').' '.substr((string) $cita->hora_inicio, 0, 5));

            if (! $startDateTime->betweenIncluded($windowStart, $windowEnd)) {
                continue;
            }

            $companyName = $this->companyName($cita);

            $this->sendLifecycleMail($cita, [
                'subject' => 'Recordatorio de tu cita en '.$companyName,
                'title' => $subject,
                'companyName' => $companyName,
                'intro' => $companyName.' te recuerda que tu cita esta programada dentro de '.$hoursBefore.' horas.',
                'detailLines' => $this->baseDetails($cita),
                'footer' => 'Si necesitas ayuda antes de llegar, puedes comunicarte con el equipo de '.$companyName.'.',
            ]);

            $cita->forceFill([
                $column => now(),
            ])->save();

            $sentCount++;
        }

        return $sentCount;
    }

    protected function sendLifecycleMail(Cita $cita, array $payload): void
    {
        $emails = $this->resolveEmails($cita);

        if (empty($emails)) {
            return;
        }

        try {
            Mail::to($emails)->send(new CitaLifecycleMail($payload));
        } catch (\Throwable $exception) {
            Log::error('No se pudo enviar correo de cita.', [
                'cita_id' => $cita->id,
                'emails' => $emails,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function resolveEmails(Cita $cita): array
    {
        $emails = [
            $cita->cliente?->correo,
            $cita->colaborador?->socio?->correo,
        ];

        return collect($emails)
            ->filter(fn ($email) => filter_var((string) $email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => (string) $email)
            ->unique()
            ->values()
            ->all();
    }

    protected function baseDetails(Cita $cita): array
    {
        $cliente = $cita->nombreClienteMostrado();
        $empleado = trim(($cita->colaborador?->socio?->nombres ?? '').' '.($cita->colaborador?->socio?->apellidos ?? '')) ?: 'Sin empleado';
        $sucursal = $cita->sucursal?->nombre ?? 'Sin sucursal';
        $fecha = $this->formatDate(optional($cita->fecha)->format('Y-m-d'));
        $horaInicio = substr((string) $cita->hora_inicio, 0, 5);
        $horaSalida = substr((string) $cita->hora_salida, 0, 5);

        return [
            'Cliente: '.$cliente,
            'Empleado: '.$empleado,
            'Sucursal: '.$sucursal,
            'Fecha: '.$fecha,
            'Hora: '.$horaInicio.' - '.$horaSalida,
            'Servicio: '.(string) $cita->descripcion,
        ];
    }

    protected function companyName(Cita $cita): string
    {
        $name = trim((string) $cita->xnova?->nombre);

        return $name !== '' ? $name : 'nuestro equipo';
    }

    protected function formatDate(?string $date): string
    {
        if (blank($date)) {
            return 'Sin fecha';
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    protected function relations(): array
    {
        return [
            'cliente:dni,nombres,apellidos,correo',
            'colaborador:dni,sucursal_Id',
            'colaborador.socio:dni,nombres,apellidos,correo',
            'sucursal:id,nombre',
            'estado:id,nombre',
            'xnova:id,nombre',
        ];
    }
}

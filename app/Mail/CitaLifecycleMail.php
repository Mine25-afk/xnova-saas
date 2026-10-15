<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CitaLifecycleMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) ($this->payload['subject'] ?? 'Actualizacion de cita')
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.citas.lifecycle',
            with: [
                'title' => (string) ($this->payload['title'] ?? 'Actualizacion de cita'),
                'companyName' => (string) ($this->payload['companyName'] ?? 'xNova'),
                'intro' => (string) ($this->payload['intro'] ?? ''),
                'detailLines' => (array) ($this->payload['detailLines'] ?? []),
                'footer' => (string) ($this->payload['footer'] ?? ''),
            ]
        );
    }
}

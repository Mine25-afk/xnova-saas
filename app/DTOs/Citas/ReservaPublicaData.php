<?php

namespace App\DTOs\Citas;

/**
 * Reserva hecha por un cliente desde el formulario público de la empresa.
 */
final readonly class ReservaPublicaData
{
    public const MODO_GOOGLE = 'google';

    public const MODO_INVITADO = 'guest';

    public function __construct(
        public string $modo,
        public int $productoId,
        public string $colaboradorDni,
        public string $fecha,
        public string $horaInicio,
        public string $horaSalida,
        public ?string $comentario = null,
        public ?string $googleEmail = null,
        public ?string $googleNombre = null,
    ) {}

    public function esInvitado(): bool
    {
        return $this->modo === self::MODO_INVITADO;
    }
}

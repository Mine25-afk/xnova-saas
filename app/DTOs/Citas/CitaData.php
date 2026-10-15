<?php

namespace App\DTOs\Citas;

/**
 * Datos de una cita capturada desde la agenda interna.
 */
final readonly class CitaData
{
    public function __construct(
        public string $clienteDni,
        public string $colaboradorDni,
        public int $sucursalId,
        public string $fecha,
        public string $horaInicio,
        public string $horaSalida,
        public string $descripcion,
        public ?int $productoId = null,
        public ?string $comentario = null,
        public bool $confirmarFueraHorario = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return [
            'cliente_dni' => $this->clienteDni,
            'colaborador_dni' => $this->colaboradorDni,
            'sucursal_Id' => $this->sucursalId,
            'producto_id' => $this->productoId,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_salida' => $this->horaSalida,
            'descripcion' => $this->descripcion,
            'comentario' => $this->comentario,
        ];
    }
}

<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\CitaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    /** @use HasFactory<CitaFactory> */
    use BelongsToTenant, HasFactory;

    public const ESTADO_RESERVADA_ID = 1;

    public const ESTADO_TERMINADA_ID = 2;

    public const ESTADO_CANCELADA_ID = 3;

    public const ESTADO_AUTORIZADA_ID = 4;

    /** Estados que ocupan agenda (bloquean el horario del colaborador). */
    public const ESTADOS_ACTIVOS = [self::ESTADO_RESERVADA_ID, self::ESTADO_AUTORIZADA_ID];

    protected $table = 'citas';

    protected $fillable = [
        'cliente_dni',
        'colaborador_dni',
        'sucursal_Id',
        'producto_id',
        'fecha',
        'hora_inicio',
        'hora_salida',
        'estado_id',
        'descripcion',
        'comentario',
        'notificacion_creada_at',
        'notificacion_reprogramada_at',
        'notificacion_cancelada_at',
        'recordatorio_24h_at',
        'recordatorio_2h_at',
        'cliente_nombre_publico',
        'cliente_identidad_publica',
        'xnova_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'notificacion_creada_at' => 'datetime',
            'notificacion_reprogramada_at' => 'datetime',
            'notificacion_cancelada_at' => 'datetime',
            'recordatorio_24h_at' => 'datetime',
            'recordatorio_2h_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SocioNegocio, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'cliente_dni', 'dni');
    }

    /**
     * @return BelongsTo<Colaborador, $this>
     */
    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_dni', 'dni');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_Id');
    }

    /**
     * @return BelongsTo<Producto, $this>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * @return BelongsTo<Estado, $this>
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function nombreClienteMostrado(): string
    {
        $cliente = trim(($this->cliente?->nombres ?? '').' '.($this->cliente?->apellidos ?? ''));

        if ($cliente !== '') {
            return $cliente;
        }

        return trim((string) $this->cliente_nombre_publico) ?: 'Sin cliente';
    }

    public function identidadClienteMostrada(): string
    {
        return (string) ($this->cliente?->dni ?: $this->cliente_identidad_publica ?: 'Sin identidad');
    }

    public function estaReservada(): bool
    {
        return (int) $this->estado_id === self::ESTADO_RESERVADA_ID;
    }

    public function estaAutorizada(): bool
    {
        return (int) $this->estado_id === self::ESTADO_AUTORIZADA_ID;
    }

    public function estaEditable(): bool
    {
        return $this->estaReservada() || $this->estaAutorizada();
    }

    public function estaActivaAgenda(): bool
    {
        return $this->estaEditable();
    }

    public function estaTerminada(): bool
    {
        return (int) $this->estado_id === self::ESTADO_TERMINADA_ID;
    }

    public function estaCancelada(): bool
    {
        return (int) $this->estado_id === self::ESTADO_CANCELADA_ID;
    }
}

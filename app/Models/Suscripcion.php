<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';

    protected $fillable = [
        'xnova_paquete_id',
        'proveedor',
        'estado',
        'plan_externo',
        'referencia_externa',
        'usuarios_incluidos',
        'usuarios_extra',
        'usuarios_totales',
        'sucursales_incluidas',
        'sucursales_extra',
        'sucursales_totales',
        'precio_base',
        'precio_usuario_extra',
        'precio_sucursal_extra',
        'monto_total',
        'moneda',
        'fecha_activacion',
        'periodo_inicio',
        'periodo_fin',
        'fecha_cancelacion',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'usuarios_incluidos' => 'integer',
            'usuarios_extra' => 'integer',
            'usuarios_totales' => 'integer',
            'sucursales_incluidas' => 'integer',
            'sucursales_extra' => 'integer',
            'sucursales_totales' => 'integer',
            'precio_base' => 'decimal:2',
            'precio_usuario_extra' => 'decimal:2',
            'precio_sucursal_extra' => 'decimal:2',
            'monto_total' => 'decimal:2',
            'fecha_activacion' => 'datetime',
            'periodo_inicio' => 'datetime',
            'periodo_fin' => 'datetime',
            'fecha_cancelacion' => 'datetime',
            'payload' => 'array',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanzasMovimiento extends Model
{
    public const TIPO_INGRESO = 'ingreso';
    public const TIPO_GASTO = 'gasto';
    public const TIPO_TRANSFERENCIA = 'transferencia';
    public const TIPO_AJUSTE = 'ajuste';

    protected $table = 'finanzas_movimientos';

    protected $fillable = [
        'xnova_id',
        'finanzas_cuenta_id',
        'finanzas_cuenta_destino_id',
        'finanzas_subcuenta_id',
        'finanzas_subcuenta_destino_id',
        'finanzas_propietario_id',
        'finanzas_deuda_id',
        'finanzas_cuenta_por_cobrar_id',
        'tipo',
        'descripcion',
        'categoria',
        'monto',
        'monto_original',
        'fecha',
        'anulado_en',
        'motivo_anulacion',
        'pendiente',
        'confirmado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_original' => 'decimal:2',
        'fecha' => 'date',
        'anulado_en' => 'datetime',
        'pendiente' => 'boolean',
        'confirmado_en' => 'datetime',
    ];

    public function isAnulado(): bool
    {
        return $this->anulado_en !== null;
    }

    public function isPendiente(): bool
    {
        return (bool) $this->pendiente;
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_id');
    }

    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_destino_id');
    }

    public function subcuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasSubcuenta::class, 'finanzas_subcuenta_id');
    }

    public function subcuentaDestino(): BelongsTo
    {
        return $this->belongsTo(FinanzasSubcuenta::class, 'finanzas_subcuenta_destino_id');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(FinanzasPropietario::class, 'finanzas_propietario_id');
    }

    public function deuda(): BelongsTo
    {
        return $this->belongsTo(FinanzasDeuda::class, 'finanzas_deuda_id');
    }

    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuentaPorCobrar::class, 'finanzas_cuenta_por_cobrar_id');
    }
}

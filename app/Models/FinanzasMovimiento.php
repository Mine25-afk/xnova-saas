<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\FinanzasMovimientoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanzasMovimiento extends Model
{
    /** @use HasFactory<FinanzasMovimientoFactory> */
    use BelongsToTenant, HasFactory;

    public const TIPO_INGRESO = 'ingreso';

    public const TIPO_GASTO = 'gasto';

    public const TIPO_TRANSFERENCIA = 'transferencia';

    public const TIPO_AJUSTE = 'ajuste';

    public const TIPOS = [
        self::TIPO_INGRESO => 'Ingreso',
        self::TIPO_GASTO => 'Gasto',
        self::TIPO_TRANSFERENCIA => 'Transferencia',
        self::TIPO_AJUSTE => 'Ajuste',
    ];

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

    /**
     * Movimientos que afectan saldos: ni anulados ni pendientes de confirmar.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAplicados(Builder $query): void
    {
        $query->whereNull('anulado_en')->where('pendiente', false);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNoAnulados(Builder $query): void
    {
        $query->whereNull('anulado_en');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePendientesDeConfirmar(Builder $query): void
    {
        $query->where('pendiente', true)->whereNull('anulado_en');
    }

    public function isAnulado(): bool
    {
        return $this->anulado_en !== null;
    }

    public function isPendiente(): bool
    {
        return (bool) $this->pendiente;
    }

    /**
     * @return BelongsTo<FinanzasCuenta, $this>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_id');
    }

    /**
     * @return BelongsTo<FinanzasCuenta, $this>
     */
    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_destino_id');
    }

    /**
     * @return BelongsTo<FinanzasSubcuenta, $this>
     */
    public function subcuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasSubcuenta::class, 'finanzas_subcuenta_id');
    }

    /**
     * @return BelongsTo<FinanzasSubcuenta, $this>
     */
    public function subcuentaDestino(): BelongsTo
    {
        return $this->belongsTo(FinanzasSubcuenta::class, 'finanzas_subcuenta_destino_id');
    }

    /**
     * @return BelongsTo<FinanzasPropietario, $this>
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(FinanzasPropietario::class, 'finanzas_propietario_id');
    }

    /**
     * @return BelongsTo<FinanzasDeuda, $this>
     */
    public function deuda(): BelongsTo
    {
        return $this->belongsTo(FinanzasDeuda::class, 'finanzas_deuda_id');
    }

    /**
     * @return BelongsTo<FinanzasCuentaPorCobrar, $this>
     */
    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuentaPorCobrar::class, 'finanzas_cuenta_por_cobrar_id');
    }
}

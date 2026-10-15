<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanzasIngresoFijo extends Model
{
    use BelongsToTenant;

    protected $table = 'finanzas_ingresos_fijos';

    protected $fillable = [
        'xnova_id',
        'finanzas_propietario_id',
        'finanzas_cuenta_id',
        'nombre',
        'icono',
        'monto',
        'preguntar_al_usar',
        'saldo_restante',
        'frecuencia',
        'dia_mes',
        'active',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_restante' => 'decimal:2',
        'preguntar_al_usar' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * @return BelongsTo<FinanzasPropietario, $this>
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(FinanzasPropietario::class, 'finanzas_propietario_id');
    }

    /**
     * @return BelongsTo<FinanzasCuenta, $this>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_id');
    }
}

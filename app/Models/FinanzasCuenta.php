<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\FinanzasCuentaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasCuenta extends Model
{
    /** @use HasFactory<FinanzasCuentaFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'finanzas_cuentas';

    protected $fillable = [
        'xnova_id',
        'finanzas_propietario_id',
        'nombre',
        'tipo',
        'icono',
        'moneda',
        'active',
    ];

    protected $casts = [
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
     * @return HasMany<FinanzasMovimiento, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_cuenta_id');
    }

    /**
     * @return HasMany<FinanzasSubcuenta, $this>
     */
    public function subcuentas(): HasMany
    {
        return $this->hasMany(FinanzasSubcuenta::class, 'finanzas_cuenta_id');
    }
}

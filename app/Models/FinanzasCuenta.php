<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasCuenta extends Model
{
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

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(FinanzasPropietario::class, 'finanzas_propietario_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_cuenta_id');
    }

    public function subcuentas(): HasMany
    {
        return $this->hasMany(FinanzasSubcuenta::class, 'finanzas_cuenta_id');
    }
}

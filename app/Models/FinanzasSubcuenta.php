<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasSubcuenta extends Model
{
    protected $table = 'finanzas_subcuentas';

    protected $fillable = [
        'xnova_id',
        'finanzas_cuenta_id',
        'nombre',
        'icono',
        'meta',
        'es_general',
        'active',
    ];

    protected $casts = [
        'meta' => 'decimal:2',
        'es_general' => 'boolean',
        'active' => 'boolean',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_subcuenta_id');
    }
}

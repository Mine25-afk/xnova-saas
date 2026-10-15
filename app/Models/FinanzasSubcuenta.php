<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\FinanzasSubcuentaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bolsillo de una cuenta. Cada cuenta tiene un bolsillo "General" (es_general).
 */
class FinanzasSubcuenta extends Model
{
    /** @use HasFactory<FinanzasSubcuentaFactory> */
    use BelongsToTenant, HasFactory;

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

    /**
     * @return BelongsTo<FinanzasCuenta, $this>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(FinanzasCuenta::class, 'finanzas_cuenta_id');
    }

    /**
     * @return HasMany<FinanzasMovimiento, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_subcuenta_id');
    }
}

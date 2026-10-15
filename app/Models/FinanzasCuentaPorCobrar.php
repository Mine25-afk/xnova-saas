<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\FinanzasCuentaPorCobrarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasCuentaPorCobrar extends Model
{
    /** @use HasFactory<FinanzasCuentaPorCobrarFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'finanzas_cuentas_por_cobrar';

    protected $fillable = [
        'xnova_id',
        'nombre',
        'deudor',
        'icono',
        'categoria',
        'monto_original',
        'saldo_pendiente',
        'fecha',
        'fecha_vencimiento',
        'notas',
        'active',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'active' => 'boolean',
    ];

    public function estaCobrada(): bool
    {
        return (float) $this->saldo_pendiente <= 0;
    }

    /**
     * @return HasMany<FinanzasMovimiento, $this>
     */
    public function cobros(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_cuenta_por_cobrar_id');
    }
}

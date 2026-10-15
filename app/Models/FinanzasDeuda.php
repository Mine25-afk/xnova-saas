<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasDeuda extends Model
{
    protected $table = 'finanzas_deudas';

    protected $fillable = [
        'xnova_id',
        'nombre',
        'acreedor',
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

    public function estaPagada(): bool
    {
        return (float) $this->saldo_pendiente <= 0;
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(FinanzasMovimiento::class, 'finanzas_deuda_id');
    }
}

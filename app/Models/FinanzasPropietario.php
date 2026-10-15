<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasPropietario extends Model
{
    protected $table = 'finanzas_propietarios';

    protected $fillable = [
        'xnova_id',
        'nombre',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function xnova(): BelongsTo
    {
        return $this->belongsTo(Xnova::class, 'xnova_id');
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(FinanzasCuenta::class, 'finanzas_propietario_id');
    }
}

<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\FinanzasPropietarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanzasPropietario extends Model
{
    /** @use HasFactory<FinanzasPropietarioFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'finanzas_propietarios';

    protected $fillable = [
        'xnova_id',
        'nombre',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return HasMany<FinanzasCuenta, $this>
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(FinanzasCuenta::class, 'finanzas_propietario_id');
    }
}

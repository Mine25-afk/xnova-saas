<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    use BelongsToTenant;

    protected $table = 'horarios';

    protected $fillable = [
        'nombre',
        'active',
        'xnova_id',
    ];

    /**
     * @return HasMany<HorarioDetalle, $this>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(HorarioDetalle::class, 'horario_id');
    }
}

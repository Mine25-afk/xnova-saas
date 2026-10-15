<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = "horarios";

    protected $fillable = [
        "nombre",
        "active",
        "xnova_id",
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(HorarioDetalle::class, 'horario_id');
    }
}

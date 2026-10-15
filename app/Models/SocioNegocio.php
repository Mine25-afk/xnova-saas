<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocioNegocio extends Model
{
    protected $table = "socio_negocios";
    protected $primaryKey = 'dni';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        "dni",
        "nombres",
        "apellidos",
        "fecha_nacimiento",
        "telefono",
        "correo",
        "sexo",
        "tipo",
        "xnova_id",
        "active",
        "es_por_defecto",
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'active' => 'boolean',
        'es_por_defecto' => 'boolean',
    ];

    public function colaborador()
    {
        return $this->hasOne(Colaborador::class, 'dni', 'dni');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SocioLog::class, 'Socio_Dni', 'dni');
    }
}

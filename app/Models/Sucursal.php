<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    protected $table = "sucursales";

    protected $fillable = ['nombre','horario_id','xnova_id'];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sucursal_Id');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'sucursal_Id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SucursalLog::class, 'Sucursal_Id');
    }
}

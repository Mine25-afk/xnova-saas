<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = [
        'modulo_id',
        'pantalla_id',
        'boton_id',
        'nombre',
        'codigo',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }

    public function pantalla(): BelongsTo
    {
        return $this->belongsTo(Pantalla::class, 'pantalla_id');
    }

    public function boton(): BelongsTo
    {
        return $this->belongsTo(Boton::class, 'boton_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id')
            ->withTimestamps();
    }
}

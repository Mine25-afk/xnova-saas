<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pantalla extends Model
{
    protected $table = 'pantallas';

    protected $fillable = [
        'nombre',
        'modulo_id',
        'codigo',
        'ruta',
    ];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }

    public function botones(): HasMany
    {
        return $this->hasMany(Boton::class, 'pantalla_id');
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'pantalla_id');
    }
}

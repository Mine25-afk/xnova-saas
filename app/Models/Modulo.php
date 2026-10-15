<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    protected $table = 'modulos';

    protected $fillable = [
        'nombre',
        'codigo',
    ];

    public function pantallas(): HasMany
    {
        return $this->hasMany(Pantalla::class, 'modulo_id');
    }

    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(XnovaTipo::class, 'xnova_tipo_modulo', 'modulo_id', 'xnova_tipo_id')
            ->withTimestamps();
    }
}

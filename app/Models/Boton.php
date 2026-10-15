<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boton extends Model
{
    protected $table = 'botones';

    protected $fillable = [
        'nombre',
        'pantalla_id',
        'codigo',
    ];

    public function pantalla(): BelongsTo
    {
        return $this->belongsTo(Pantalla::class, 'pantalla_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    protected $table = "puestos";

    protected $fillable = [
        'nombre',
        'xnova_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

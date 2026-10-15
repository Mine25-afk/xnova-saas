<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    public const CITA_RESERVADA_ID = 1;

    public const CITA_TERMINADA_ID = 2;

    public const CITA_CANCELADA_ID = 3;

    public const CITA_AUTORIZADA_ID = 4;

    public $timestamps = false;

    protected $table = 'estados';

    protected $fillable = ['id', 'nombre'];
}

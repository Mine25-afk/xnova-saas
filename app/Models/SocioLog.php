<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocioLog extends Model
{
    protected $table = 'socios_log';

    public $timestamps = false;

    protected $fillable = [
        'Socio_Dni',
        'Accion',
        'Fecha',
        'User_Modifica_Id',
    ];

    protected function casts(): array
    {
        return [
            'Fecha' => 'datetime',
        ];
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'Socio_Dni', 'dni');
    }

    public function usuarioModifica(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_Modifica_Id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SucursalLog extends Model
{
    protected $table = 'sucursales_log';

    public $timestamps = false;

    protected $fillable = [
        'Sucursal_Id',
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

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'Sucursal_Id');
    }

    public function usuarioModifica(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_Modifica_Id');
    }
}

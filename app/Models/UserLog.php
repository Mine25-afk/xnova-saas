<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    protected $table = 'User_Logs';

    public $timestamps = false;

    protected $fillable = [
        'User_Id',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_Id');
    }

    public function usuarioModifica(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_Modifica_Id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleLog extends Model
{
    protected $table = 'roles_log';

    public $timestamps = false;

    protected $fillable = [
        'Role_Id',
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

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'Role_Id');
    }

    public function usuarioModifica(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_Modifica_Id');
    }
}

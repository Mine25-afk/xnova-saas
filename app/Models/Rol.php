<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'xnova_id',
    ];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id')
            ->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RoleLog::class, 'Role_Id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}

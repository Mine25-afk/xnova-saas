<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\RolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    /** @use HasFactory<RolFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'xnova_id',
    ];

    /**
     * @return BelongsToMany<Permiso, $this>
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<RoleLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RoleLog::class, 'Role_Id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}

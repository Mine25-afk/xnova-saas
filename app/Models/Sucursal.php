<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\SucursalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    /** @use HasFactory<SucursalFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'sucursales';

    protected $fillable = ['nombre', 'horario_id', 'xnova_id'];

    /**
     * @return BelongsTo<Horario, $this>
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sucursal_Id');
    }

    /**
     * @return HasMany<Colaborador, $this>
     */
    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'sucursal_Id');
    }

    /**
     * @return HasMany<SucursalLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SucursalLog::class, 'Sucursal_Id');
    }
}

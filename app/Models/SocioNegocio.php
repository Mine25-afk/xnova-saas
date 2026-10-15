<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\SocioNegocioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocioNegocio extends Model
{
    /** @use HasFactory<SocioNegocioFactory> */
    use BelongsToTenant, HasFactory;

    public const TIPO_CLIENTE = 'C';

    public const TIPO_COLABORADOR = 'E';

    public $incrementing = false;

    protected $table = 'socio_negocios';

    protected $primaryKey = 'dni';

    protected $keyType = 'string';

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'telefono',
        'correo',
        'sexo',
        'tipo',
        'xnova_id',
        'active',
        'es_por_defecto',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'active' => 'boolean',
        'es_por_defecto' => 'boolean',
    ];

    /**
     * @return HasOne<Colaborador, $this>
     */
    public function colaborador(): HasOne
    {
        return $this->hasOne(Colaborador::class, 'dni', 'dni');
    }

    /**
     * @return HasMany<SocioLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SocioLog::class, 'Socio_Dni', 'dni');
    }
}

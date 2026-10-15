<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\ColaboradorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Colaborador extends Model
{
    /** @use HasFactory<ColaboradorFactory> */
    use BelongsToTenant, HasFactory;

    public $incrementing = false;

    protected $table = 'colaboradores';

    protected $primaryKey = 'dni';

    protected $keyType = 'string';

    protected $fillable = [
        'dni',
        'puesto_id',
        'sucursal_Id',
        'xnova_id',
    ];

    /**
     * @return BelongsTo<SocioNegocio, $this>
     */
    public function socio(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'dni', 'dni');
    }

    /**
     * @return BelongsTo<Puesto, $this>
     */
    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    /**
     * @return BelongsTo<Sucursal, $this>
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_Id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    protected $table = 'colaboradores';
    protected $primaryKey = 'dni';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'dni',
        'puesto_id',
        'sucursal_Id',
        'xnova_id',
    ];

    public function socio()
    {
        return $this->belongsTo(SocioNegocio::class, 'dni', 'dni');
    }

    public function puesto()
    {
        return $this->belongsTo(Puesto::class, 'puesto_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_Id');
    }
}

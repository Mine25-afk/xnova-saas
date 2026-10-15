<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'usuarios_incluidos',
        'precio_usuario_extra',
        'sucursales_incluidas',
        'precio_sucursal_extra',
        'paypal_plan_id',
        'paypal_product_id',
        'moneda',
        'activo',
    ];

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'paquete_modulo', 'paquete_id', 'modulo_id')
            ->withTimestamps();
    }
}

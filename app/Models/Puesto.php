<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Database\Factories\PuestoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    /** @use HasFactory<PuestoFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'puestos';

    protected $fillable = [
        'nombre',
        'xnova_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

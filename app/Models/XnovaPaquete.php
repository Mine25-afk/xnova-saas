<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XnovaPaquete extends Model
{
    protected $table = 'xnova_paquete';

    protected $fillable = [
        'xnova_id',
        'paquete_id',
        'precio_mensual',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    public function xnova(): BelongsTo
    {
        return $this->belongsTo(Xnova::class, 'xnova_id');
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'xnova_paquete_id');
    }

    protected function casts(): array
    {
        return [
            'precio_mensual' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activo' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioDetalle extends Model
{
    protected $table = 'horarios_detalles';

    protected $fillable = [
        'nombre',
        'dia',
        'hora_inicio',
        'hora_salida',
        'receso_incio',
        'receso_salida',
        'horario_id',
    ];

    protected function casts(): array
    {
        return [
            'dia' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Horario, $this>
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }
}

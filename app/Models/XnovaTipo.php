<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XnovaTipo extends Model
{
    public const TIPO_CLINICA = 'Clinica';
    public const TIPO_SALONES = 'Salones';
    public const TIPO_BARBERIAS = 'Barberias';
    public const TIPO_CATALOGOS = 'Catalogos';
    public const TIPO_FINANZAS = 'Finanzas Personales y en Pareja';

    protected $table = 'xNova_Tipo';

    protected $fillable = [
        'tipo',
    ];

    public function xnovas(): HasMany
    {
        return $this->hasMany(Xnova::class, 'xnova_tipo_id');
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'xnova_tipo_modulo', 'xnova_tipo_id', 'modulo_id')
            ->withTimestamps();
    }

    public function isCatalogo(): bool
    {
        return strcasecmp($this->tipo, self::TIPO_CATALOGOS) === 0;
    }

    public function isFinanzas(): bool
    {
        return strcasecmp($this->tipo, self::TIPO_FINANZAS) === 0;
    }
}

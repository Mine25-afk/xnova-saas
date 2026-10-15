<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Xnova extends Model
{
    protected $table = 'xnovas';

    protected $fillable = [
        'nombre',
        'xnova_tipo_id',
        'reservacion_uid',
        'moneda_default',
        'active',
        'user_id',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(XnovaTipo::class, 'xnova_tipo_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function xnovaPaquetes(): HasMany
    {
        return $this->hasMany(XnovaPaquete::class, 'xnova_id');
    }

    public function usaFlujoCatalogos(): bool
    {
        return $this->relationLoaded('tipo')
            ? (bool) $this->tipo?->isCatalogo()
            : strcasecmp((string) $this->tipo()?->value('tipo'), XnovaTipo::TIPO_CATALOGOS) === 0;
    }

    protected static function booted(): void
    {
        static::creating(function (self $xnova): void {
            if (filled($xnova->reservacion_uid)) {
                return;
            }

            $xnova->reservacion_uid = (string) Str::lower(Str::ulid());
        });
    }
}

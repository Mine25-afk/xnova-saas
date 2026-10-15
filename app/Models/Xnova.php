<?php

namespace App\Models;

use Database\Factories\XnovaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Empresa (tenant). Es la raíz del aislamiento: no lleva scope propio.
 */
class Xnova extends Model
{
    /** @use HasFactory<XnovaFactory> */
    use HasFactory;

    public const MONEDA_POR_DEFECTO = 'LPS';

    protected $table = 'xnovas';

    protected $fillable = [
        'nombre',
        'xnova_tipo_id',
        'reservacion_uid',
        'moneda_default',
        'active',
        'user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $xnova): void {
            if (filled($xnova->reservacion_uid)) {
                return;
            }

            $xnova->reservacion_uid = Str::lower((string) Str::ulid());
        });
    }

    /**
     * @return BelongsTo<XnovaTipo, $this>
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(XnovaTipo::class, 'xnova_tipo_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<XnovaPaquete, $this>
     */
    public function xnovaPaquetes(): HasMany
    {
        return $this->hasMany(XnovaPaquete::class, 'xnova_id');
    }

    public function usaFlujoCatalogos(): bool
    {
        return $this->relationLoaded('tipo')
            ? (bool) $this->tipo?->isCatalogo()
            : strcasecmp((string) $this->tipo()->value('tipo'), XnovaTipo::TIPO_CATALOGOS) === 0;
    }

    public function monedaPorDefecto(): string
    {
        return $this->moneda_default ?: self::MONEDA_POR_DEFECTO;
    }
}

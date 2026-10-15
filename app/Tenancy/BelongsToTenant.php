<?php

namespace App\Tenancy;

use App\Models\Xnova;
use App\Tenancy\Exceptions\TenantMismatchException;
use App\Tenancy\Exceptions\TenantNotResolvedException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convierte un modelo en dato de una empresa: lo filtra con TenantScope, le
 * asigna xnova_id al crearlo e impide moverlo de empresa.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $current = app(TenantContext::class)->id();
            $assigned = $model->getAttribute(TenantScope::COLUMN);

            if (blank($assigned)) {
                if ($current === null) {
                    throw new TenantNotResolvedException('No se puede crear '.class_basename($model).' sin una empresa activa.');
                }

                $model->setAttribute(TenantScope::COLUMN, $current);

                return;
            }

            if ($current !== null && (int) $assigned !== $current) {
                throw new TenantMismatchException(class_basename($model));
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty(TenantScope::COLUMN)) {
                throw new TenantMismatchException(class_basename($model));
            }
        });
    }

    /**
     * @return BelongsTo<Xnova, $this>
     */
    public function xnova(): BelongsTo
    {
        return $this->belongsTo(Xnova::class, TenantScope::COLUMN);
    }

    /**
     * Consulta explícita de otra empresa (jobs, superadmin). Quita el scope global.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForTenant(Builder $query, int $tenantId): void
    {
        $query->withoutGlobalScope(TenantScope::class)
            ->where($query->qualifyColumn(TenantScope::COLUMN), $tenantId);
    }

    /**
     * Consulta deliberada sobre todas las empresas. Usar solo en procesos internos.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAcrossTenants(Builder $query): void
    {
        $query->withoutGlobalScope(TenantScope::class);
    }
}

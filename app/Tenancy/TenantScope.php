<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita toda consulta Eloquent a la empresa activa del TenantContext.
 */
final class TenantScope implements Scope
{
    public const COLUMN = 'xnova_id';

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            // Fail-closed: sin empresa resuelta la consulta no devuelve filas.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn(self::COLUMN), $tenantId);
    }
}

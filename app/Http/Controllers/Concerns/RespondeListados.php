<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Respuesta JSON de los listados AJAX: filas, tarjetas y paginación ya renderizadas.
 */
trait RespondeListados
{
    /**
     * @param  LengthAwarePaginator<int, mixed>  $registros
     * @return array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}
     */
    protected function paginationMeta(LengthAwarePaginator $registros): array
    {
        return [
            'current_page' => $registros->currentPage(),
            'last_page' => $registros->lastPage(),
            'per_page' => $registros->perPage(),
            'total' => $registros->total(),
            'from' => $registros->firstItem(),
            'to' => $registros->lastItem(),
        ];
    }

    /**
     * Renderiza los parciales rows/cards/pagination de una carpeta de vistas.
     *
     * @param  LengthAwarePaginator<int, mixed>  $registros
     * @param  array<string, mixed>  $datos  variables que reciben los parciales
     * @param  list<string>  $parciales
     * @return array<string, mixed>
     */
    protected function listado(string $carpeta, string $variable, LengthAwarePaginator $registros, array $datos = [], array $parciales = ['rows', 'cards']): array
    {
        $respuesta = [];

        foreach ($parciales as $parcial) {
            $respuesta[$parcial] = view("{$carpeta}.{$parcial}", [$variable => $registros] + $datos)->render();
        }

        $respuesta['pagination'] = view("{$carpeta}.pagination", [$variable => $registros])->render();
        $respuesta['meta'] = $this->paginationMeta($registros);

        return $respuesta;
    }
}

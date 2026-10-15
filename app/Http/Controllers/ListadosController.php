<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentXnova;
use App\Models\SocioNegocio;
use App\Models\Colaborador;
use App\Models\Estado;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ListadosController extends Controller
{
    use ResolvesCurrentXnova;

    public function obtener(string $tipo, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $opciones = match ($tipo) {
            'roles' => $this->obtenerRoles($search),
            'sucursales' => $this->obtenerSucursales($search),
            'empleados' => $this->obtenerEmpleados($search),
            'clientes-activos' => $this->obtenerClientesActivos($search),
            'colaboradores-activos' => $this->obtenerColaboradoresActivos($search),
            'productos' => $this->obtenerProductos($search),
            'estados-citas' => $this->obtenerEstadosCitas(),
            default => abort(404),
        };

        return response()->json([
            'tipo' => $tipo,
            'opciones' => $opciones,
        ]);
    }

    protected function obtenerRoles(string $search): array
    {
        return Rol::query()
            ->select(['id', 'nombre'])
            ->where('xnova_id', $this->currentXnovaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->get()
            ->map(fn (Rol $rol): array => [
                'valor' => $rol->id,
                'texto' => $rol->nombre,
            ])
            ->values()
            ->all();
    }

    protected function obtenerSucursales(string $search): array
    {
        return Sucursal::query()
            ->select(['id', 'nombre'])
            ->where('xnova_id', $this->currentXnovaId())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->get()
            ->map(fn (Sucursal $sucursal): array => [
                'valor' => $sucursal->id,
                'texto' => $sucursal->nombre,
            ])
            ->values()
            ->all();
    }

    protected function obtenerEmpleados(string $search): array
    {
        return SocioNegocio::query()
            ->select(['dni', 'nombres', 'apellidos', 'active'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('tipo', 'E')
            ->whereHas('colaborador', function (Builder $query): void {
                $query->where('xnova_id', $this->currentXnovaId());
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('active')
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get()
            ->map(function (SocioNegocio $empleado): array {
                $nombreCompleto = trim($empleado->nombres.' '.$empleado->apellidos);

                return [
                    'valor' => $empleado->dni,
                    'texto' => $nombreCompleto.($empleado->active ? '' : ' (Inactivo)'),
                ];
            })
            ->values()
            ->all();
    }

    protected function obtenerClientesActivos(string $search): array
    {
        return SocioNegocio::query()
            ->select(['dni', 'nombres', 'apellidos'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('tipo', 'C')
            ->where('active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get()
            ->map(function (SocioNegocio $cliente): array {
                return [
                    'valor' => $cliente->dni,
                    'texto' => trim($cliente->nombres.' '.$cliente->apellidos),
                ];
            })
            ->values()
            ->all();
    }

    protected function obtenerColaboradoresActivos(string $search): array
    {
        return Colaborador::query()
            ->with([
                'socio:dni,nombres,apellidos,active',
                'sucursal:id,nombre',
            ])
            ->where('xnova_id', $this->currentXnovaId())
            ->whereHas('socio', function (Builder $query) use ($search): void {
                $query
                    ->where('xnova_id', $this->currentXnovaId())
                    ->where('tipo', 'E')
                    ->where('active', true)
                    ->when($search !== '', function (Builder $subQuery) use ($search): void {
                        $subQuery->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery
                                ->where('dni', 'like', "%{$search}%")
                                ->orWhere('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%");
                        });
                    });
            })
            ->orderBy('dni')
            ->get()
            ->map(function (Colaborador $colaborador): array {
                $nombre = trim(($colaborador->socio?->nombres ?? '').' '.($colaborador->socio?->apellidos ?? ''));
                $sucursal = $colaborador->sucursal?->nombre ? ' - '.$colaborador->sucursal->nombre : '';

                return [
                    'valor' => $colaborador->dni,
                    'texto' => $nombre.$sucursal,
                ];
            })
            ->values()
            ->all();
    }

    protected function obtenerEstadosCitas(): array
    {
        return Estado::query()
            ->select(['id', 'nombre'])
            ->whereIn('id', [
                Estado::CITA_RESERVADA_ID,
                Estado::CITA_TERMINADA_ID,
                Estado::CITA_CANCELADA_ID,
                Estado::CITA_AUTORIZADA_ID,
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (Estado $estado): array => [
                'valor' => $estado->id,
                'texto' => $estado->nombre,
            ])
            ->values()
            ->all();
    }

    protected function obtenerProductos(string $search): array
    {
        return Producto::query()
            ->select(['id', 'nombre', 'precio'])
            ->where('xnova_id', $this->currentXnovaId())
            ->where('active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto): array => [
                'valor' => $producto->id,
                'texto' => $producto->nombre.' - '.number_format((float) $producto->precio, 2),
            ])
            ->values()
            ->all();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsaEmpresaActual;
use App\Models\Colaborador;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Xnova;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class InventoryController extends Controller
{
    use UsaEmpresaActual;

    public function reservation(): View
    {
        $xnova = Xnova::query()
            ->with('tipo:id,tipo')
            ->findOrFail($this->empresaId());

        $activeProducts = Producto::query()
            ->where('active', true)
            ->count();

        $branches = Sucursal::query()
            ->count();

        $collaborators = Colaborador::query()
            ->whereHas('socio', function (Builder $query): void {
                $query
                    ->where('tipo', 'E')
                    ->where('active', true);
            })
            ->count();

        $publicReservationUrl = route('reservaciones.public.form', $xnova->reservacion_uid);

        return view('inventario.reservation', [
            'empresa' => $xnova,
            'publicReservationUrl' => $publicReservationUrl,
            'requirements' => [
                [
                    'label' => 'Productos activos',
                    'count' => $activeProducts,
                    'ready' => $activeProducts > 0,
                    'hint' => 'Necesitas al menos un producto visible para publicar la reservacion.',
                ],
                [
                    'label' => 'Sucursales',
                    'count' => $branches,
                    'ready' => $branches > 0,
                    'hint' => 'La reservacion actual usa sucursales para asignar la cita.',
                ],
                [
                    'label' => 'Colaboradores',
                    'count' => $collaborators,
                    'ready' => $collaborators > 0,
                    'hint' => 'Necesitas al menos un colaborador activo para recibir reservaciones.',
                ],
            ],
            'canPreview' => $activeProducts > 0 && $branches > 0 && $collaborators > 0,
        ]);
    }

    public function reportes(): View
    {
        $productos = Producto::query()
            ->orderByDesc('active')
            ->orderBy('nombre')
            ->get();

        $activos = $productos->where('active', true);
        $inactivos = $productos->where('active', false);
        $conImagen = $productos->filter(fn (Producto $producto) => filled($producto->imagen_path));
        $precios = $productos->pluck('precio')->map(fn ($precio) => (float) $precio);

        return view('inventario.report', [
            'stats' => [
                'total' => $productos->count(),
                'active' => $activos->count(),
                'inactive' => $inactivos->count(),
                'with_image' => $conImagen->count(),
                'average_price' => $precios->count() > 0 ? $precios->avg() : 0,
                'highest_price' => $precios->count() > 0 ? $precios->max() : 0,
                'lowest_price' => $precios->count() > 0 ? $precios->min() : 0,
            ],
            'productos' => $productos,
        ]);
    }
}

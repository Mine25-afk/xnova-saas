<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\MovimientoRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasMovimiento;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use App\Services\Finanzas\MovimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MovimientoController extends BaseFinanzasController
{
    public function __construct(
        private readonly MovimientoRepository $movimientos,
        private readonly MovimientoService $movimientoService,
    ) {}

    public function index(): View
    {
        return view('finanzas.movimientos.index');
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $movimientos = $this->movimientos->paginate(
            $this->movimientos->buscar($request->busqueda())->orderByDesc('fecha')->orderByDesc('id'),
            $request->porPagina(),
        );

        return response()->json($this->listado('finanzas.movimientos.partials', 'movimientos', $movimientos));
    }

    public function create(PropietarioRepository $propietarios, CuentaRepository $cuentas, SubcuentaRepository $subcuentas): View
    {
        return view('finanzas.movimientos.form', [
            'movimiento' => new FinanzasMovimiento(['tipo' => FinanzasMovimiento::TIPO_GASTO, 'fecha' => now()]),
            'isEditing' => false,
            'propietarios' => $propietarios->activos(),
            'cuentas' => $cuentas->activas(),
            'subcuentas' => $subcuentas->activas(),
        ]);
    }

    public function store(MovimientoRequest $request): RedirectResponse
    {
        $this->movimientoService->registrar($request->toDto());

        return redirect()->route('finanzas.movimientos.create')->with('status', 'Movimiento guardado correctamente.');
    }

    public function show(FinanzasMovimiento $movimiento): View
    {
        $this->authorize('view', $movimiento);

        $movimiento->load(['propietario:id,nombre', 'cuenta:id,nombre,moneda', 'cuentaDestino:id,nombre', 'subcuenta:id,nombre', 'subcuentaDestino:id,nombre']);

        return view('finanzas.movimientos.show', [
            'movimiento' => $movimiento,
        ]);
    }

    public function edit(FinanzasMovimiento $movimiento, PropietarioRepository $propietarios, CuentaRepository $cuentas, SubcuentaRepository $subcuentas): View
    {
        $this->authorize('update', $movimiento);

        return view('finanzas.movimientos.form', [
            'movimiento' => $movimiento,
            'isEditing' => true,
            'propietarios' => $propietarios->activos(),
            'cuentas' => $cuentas->activas(),
            'subcuentas' => $subcuentas->activas(),
        ]);
    }

    public function update(MovimientoRequest $request, FinanzasMovimiento $movimiento): RedirectResponse
    {
        $this->authorize('update', $movimiento);

        $this->movimientoService->actualizar($movimiento, $request->toDto());

        return redirect()->route('finanzas.movimientos.show', $movimiento)->with('status', 'Movimiento actualizado correctamente.');
    }
}

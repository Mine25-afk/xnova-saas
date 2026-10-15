<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\CuentaPorCobrarRequest;
use App\Http\Requests\Finanzas\PagoRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasCuentaPorCobrar;
use App\Repositories\Finanzas\CuentaPorCobrarRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use App\Services\Finanzas\CuentaPorCobrarService;
use App\Services\Finanzas\SeleccionCuentasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CuentaPorCobrarController extends BaseFinanzasController
{
    public function __construct(
        private readonly CuentaPorCobrarRepository $cuentasPorCobrar,
        private readonly CuentaPorCobrarService $servicio,
    ) {}

    public function index(SeleccionCuentasService $seleccion): View
    {
        $cuentasDestino = $seleccion->cuentasConBolsillos();

        return view('finanzas.cuentas-por-cobrar.index', [
            'moneda' => $this->monedaEmpresa(),
            'cuentasDestino' => $cuentasDestino,
            'propietarios' => $seleccion->propietariosDe($cuentasDestino),
            'puedeCobrar' => $this->puede('finanzas.cuentas-por-cobrar.cobrar'),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $estado = $request->estado([CuentaPorCobrarRepository::ESTADO_PENDIENTE, CuentaPorCobrarRepository::ESTADO_COBRADA]);
        $items = $this->cuentasPorCobrar->paginate($this->cuentasPorCobrar->buscar($request->busqueda(), $estado)->orderByDesc('id'), $request->porPagina());
        $total = $this->cuentasPorCobrar->totalPendiente($request->busqueda());
        $moneda = $this->monedaEmpresa();

        $datos = ['moneda' => $moneda, 'puedeCobrar' => $this->puede('finanzas.cuentas-por-cobrar.cobrar')];

        return response()->json($this->listado('finanzas.cuentas-por-cobrar.partials', 'items', $items, $datos, ['cards']) + [
            'total_pendiente' => $total,
            'total_label' => $moneda.' '.number_format($total, 2),
        ]);
    }

    public function create(): View
    {
        return view('finanzas.cuentas-por-cobrar.form', [
            'cuentaPorCobrar' => new FinanzasCuentaPorCobrar(['active' => true, 'fecha' => now()->toDateString()]),
            'isEditing' => false,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function store(CuentaPorCobrarRequest $request): RedirectResponse
    {
        $this->servicio->crear($request->atributos());

        return redirect()->route('finanzas.cuentas-por-cobrar.create')->with('status', 'Cuenta por cobrar guardada correctamente.');
    }

    public function show(FinanzasCuentaPorCobrar $cuentaPorCobrar): View
    {
        $this->authorize('view', $cuentaPorCobrar);

        $cuentaPorCobrar->load(['cobros' => fn ($query) => $query
            ->with(['cuenta:id,nombre', 'subcuenta:id,nombre'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')]);

        return view('finanzas.cuentas-por-cobrar.show', [
            'item' => $cuentaPorCobrar,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function edit(FinanzasCuentaPorCobrar $cuentaPorCobrar): View
    {
        $this->authorize('update', $cuentaPorCobrar);

        return view('finanzas.cuentas-por-cobrar.form', [
            'cuentaPorCobrar' => $cuentaPorCobrar,
            'isEditing' => true,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function update(CuentaPorCobrarRequest $request, FinanzasCuentaPorCobrar $cuentaPorCobrar): RedirectResponse
    {
        $this->authorize('update', $cuentaPorCobrar);

        $this->servicio->actualizar($cuentaPorCobrar, $request->atributos());

        return redirect()->route('finanzas.cuentas-por-cobrar.show', $cuentaPorCobrar)->with('status', 'Cuenta por cobrar actualizada correctamente.');
    }

    public function cobrar(PagoRequest $request, FinanzasCuentaPorCobrar $cuentaPorCobrar, SubcuentaRepository $subcuentas): JsonResponse
    {
        $this->authorize('update', $cuentaPorCobrar);

        $cobro = $request->toDto();
        $bolsillo = $subcuentas->findOrFail($cobro->subcuentaId);

        return response()->json($this->servicio->registrarCobro($cuentaPorCobrar, $bolsillo, $cobro->monto, $cobro->pendiente));
    }
}

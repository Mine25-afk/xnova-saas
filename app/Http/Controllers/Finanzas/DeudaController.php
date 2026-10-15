<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\DeudaRequest;
use App\Http\Requests\Finanzas\PagoRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasDeuda;
use App\Repositories\Finanzas\DeudaRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use App\Services\Finanzas\DeudaService;
use App\Services\Finanzas\SeleccionCuentasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeudaController extends BaseFinanzasController
{
    public function __construct(
        private readonly DeudaRepository $deudas,
        private readonly DeudaService $deudaService,
    ) {}

    public function index(SeleccionCuentasService $seleccion): View
    {
        $cuentasDestino = $seleccion->cuentasConBolsillos();

        return view('finanzas.deudas.index', [
            'moneda' => $this->monedaEmpresa(),
            'cuentasDestino' => $cuentasDestino,
            'propietarios' => $seleccion->propietariosDe($cuentasDestino),
            'puedePagar' => $this->puede('finanzas.deudas.pagar'),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $estado = $request->estado([DeudaRepository::ESTADO_PENDIENTE, DeudaRepository::ESTADO_PAGADA]);
        $deudas = $this->deudas->paginate($this->deudas->buscar($request->busqueda(), $estado)->orderByDesc('id'), $request->porPagina());
        $total = $this->deudas->totalPendiente($request->busqueda());
        $moneda = $this->monedaEmpresa();

        $datos = ['moneda' => $moneda, 'puedePagar' => $this->puede('finanzas.deudas.pagar')];

        return response()->json($this->listado('finanzas.deudas.partials', 'deudas', $deudas, $datos, ['cards']) + [
            'total_pendiente' => $total,
            'total_label' => $moneda.' '.number_format($total, 2),
        ]);
    }

    public function create(): View
    {
        return view('finanzas.deudas.form', [
            'deuda' => new FinanzasDeuda(['active' => true, 'fecha' => now()->toDateString()]),
            'isEditing' => false,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function store(DeudaRequest $request): RedirectResponse
    {
        $this->deudaService->crear($request->atributos());

        return redirect()->route('finanzas.deudas.create')->with('status', 'Deuda guardada correctamente.');
    }

    public function show(FinanzasDeuda $deuda): View
    {
        $this->authorize('view', $deuda);

        $deuda->load(['pagos' => fn ($query) => $query
            ->with(['cuenta:id,nombre', 'subcuenta:id,nombre'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')]);

        return view('finanzas.deudas.show', [
            'deuda' => $deuda,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function edit(FinanzasDeuda $deuda): View
    {
        $this->authorize('update', $deuda);

        return view('finanzas.deudas.form', [
            'deuda' => $deuda,
            'isEditing' => true,
            'moneda' => $this->monedaEmpresa(),
        ]);
    }

    public function update(DeudaRequest $request, FinanzasDeuda $deuda): RedirectResponse
    {
        $this->authorize('update', $deuda);

        $this->deudaService->actualizar($deuda, $request->atributos());

        return redirect()->route('finanzas.deudas.show', $deuda)->with('status', 'Deuda actualizada correctamente.');
    }

    public function pagar(PagoRequest $request, FinanzasDeuda $deuda, SubcuentaRepository $subcuentas): JsonResponse
    {
        $this->authorize('update', $deuda);

        $pago = $request->toDto();
        $bolsillo = $subcuentas->findOrFail($pago->subcuentaId);

        return response()->json($this->deudaService->registrarPago($deuda, $bolsillo, $pago->monto, $pago->pendiente));
    }
}

<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Requests\Finanzas\AjustarCuentaRequest;
use App\Http\Requests\Finanzas\CuentaRequest;
use App\Http\Requests\ListadoRequest;
use App\Models\FinanzasCuenta;
use App\Models\FinanzasSubcuenta;
use App\Repositories\Finanzas\CuentaRepository;
use App\Repositories\Finanzas\MovimientoRepository;
use App\Repositories\Finanzas\PropietarioRepository;
use App\Repositories\Finanzas\SubcuentaRepository;
use App\Services\Finanzas\CuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CuentaController extends BaseFinanzasController
{
    public function __construct(
        private readonly CuentaRepository $cuentas,
        private readonly MovimientoRepository $movimientos,
        private readonly PropietarioRepository $propietarios,
        private readonly CuentaService $cuentaService,
    ) {}

    public function index(): View
    {
        return view('finanzas.cuentas.index', [
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function data(ListadoRequest $request): JsonResponse
    {
        $consulta = fn () => $this->cuentas->buscar($request->busqueda(), $request->propietarioId());

        $cuentas = $this->cuentas->paginate($consulta()->orderByDesc('id'), $request->porPagina());
        $saldos = $this->movimientos->saldosPorCuenta($cuentas->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all());

        // Totales por moneda sobre todas las cuentas filtradas, no solo la página actual.
        $totales = $this->cuentaService->totalesPorMoneda($consulta()->get(['id', 'moneda']));

        return response()->json(
            $this->listado('finanzas.cuentas.partials', 'cuentas', $cuentas, ['balances' => $saldos]) + ['totales' => $totales],
        );
    }

    public function create(): View
    {
        return view('finanzas.cuentas.form', [
            'cuenta' => new FinanzasCuenta(['active' => true, 'moneda' => $this->monedaEmpresa()]),
            'isEditing' => false,
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function store(CuentaRequest $request): RedirectResponse
    {
        $this->cuentaService->crear($request->atributos(), $request->saldoInicial());

        return redirect()->route('finanzas.cuentas.create')->with('status', 'Cuenta guardada correctamente.');
    }

    public function show(Request $request, FinanzasCuenta $cuenta, SubcuentaRepository $subcuentas): View
    {
        $this->authorize('view', $cuenta);

        $subcuentas->general($cuenta);
        $cuenta->load(['propietario:id,nombre', 'subcuentas' => fn ($query) => $query->orderByDesc('es_general')->orderBy('nombre')]);

        $editingBolsillo = $request->integer('edit_bolsillo') > 0
            ? $cuenta->subcuentas->firstWhere('id', $request->integer('edit_bolsillo'))
            : null;

        return view('finanzas.cuentas.show', [
            'cuenta' => $cuenta,
            'balances' => $this->movimientos->saldosPorSubcuenta($cuenta->subcuentas->pluck('id')->map(fn ($id): int => (int) $id)->all()),
            'saldoTotal' => $this->movimientos->saldoCuenta((int) $cuenta->id),
            'editingBolsillo' => $editingBolsillo instanceof FinanzasSubcuenta ? $editingBolsillo : null,
            'movimientosRecientes' => $this->movimientos->recientesDeCuenta((int) $cuenta->id),
        ]);
    }

    public function edit(FinanzasCuenta $cuenta): View
    {
        $this->authorize('update', $cuenta);

        return view('finanzas.cuentas.form', [
            'cuenta' => $cuenta,
            'isEditing' => true,
            'propietarios' => $this->propietarios->activos(),
        ]);
    }

    public function update(CuentaRequest $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->authorize('update', $cuenta);

        $this->cuentas->update($cuenta, $request->atributos());

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', 'Cuenta actualizada correctamente.');
    }

    public function ajustar(AjustarCuentaRequest $request, FinanzasCuenta $cuenta): RedirectResponse
    {
        $this->authorize('update', $cuenta);

        $ajustado = $this->cuentaService->ajustarSaldo($cuenta, $request->toDto());

        return redirect()->route('finanzas.cuentas.show', $cuenta)->with('status', $ajustado
            ? 'Saldo ajustado correctamente.'
            : 'El saldo real coincide con el actual, no hay nada que ajustar.');
    }
}
